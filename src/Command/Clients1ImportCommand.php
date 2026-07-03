<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use ZipArchive;

#[AsCommand(
    name: 'clients1',
    description: 'Importe les clients depuis un fichier XLSX en conservant uniquement les champs existants du site.'
)]
class Clients1ImportCommand extends Command
{
    /**
     * @var array<string, string[]>
     */
    private const FIELD_ALIASES = [
        'email' => ['email', 'e-mail', 'mail', 'courriel'],
        'password' => ['password', 'mot_de_passe', 'mot de passe', 'motdepasse', 'pass'],
        'companyName' => ['companyname', 'company_name', 'company', 'societe', 'société', 'nom_client', 'nom client', 'nom'],
        'customerCode' => ['customercode', 'customer_code', 'customer code', 'code_client', 'code client', 'client_code', 'code'],
        'isActive' => ['isactive', 'is_active', 'active', 'actif', 'compte actif'],
        'address1' => ['address1', 'address_1', 'adresse', 'adresse1', 'address', 'billing_address_1', 'shipping_address_1'],
        'address2' => ['address2', 'address_2', 'adresse2', 'complement_adresse', 'complément adresse', 'billing_address_2', 'shipping_address_2'],
        'city' => ['city', 'ville'],
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Chemin du fichier .xlsx à importer.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Analyse le fichier sans enregistrer en base.')
            ->addOption('skip-missing-email', null, InputOption::VALUE_NONE, 'Ignore les lignes sans email valide au lieu de générer un email technique.')
            ->addOption('no-update', null, InputOption::VALUE_NONE, 'Ne modifie pas les clients déjà existants.')
            ->addOption('default-password', null, InputOption::VALUE_REQUIRED, 'Mot de passe temporaire pour les nouveaux clients sans colonne password.')
            ->addOption('generated-email-domain', null, InputOption::VALUE_REQUIRED, 'Domaine utilisé pour les emails générés depuis le nom complet.', 'import.local')
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Nombre de lignes avant flush en base.', 100);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = (string) $input->getArgument('file');
        $dryRun = (bool) $input->getOption('dry-run');
        $skipMissingEmail = (bool) $input->getOption('skip-missing-email');
        $noUpdate = (bool) $input->getOption('no-update');
        $defaultPassword = $this->normalizeText($input->getOption('default-password'));
        $generatedEmailDomain = $this->normalizeEmailDomain((string) $input->getOption('generated-email-domain'));
        $batchSize = max(1, (int) $input->getOption('batch-size'));

        if ($generatedEmailDomain === null) {
            $io->error('Le domaine des emails générés est invalide.');

            return Command::FAILURE;
        }

        if ($defaultPassword !== null && mb_strlen($defaultPassword) < 6) {
            $io->error('Le mot de passe par défaut doit contenir au moins 6 caractères.');

            return Command::FAILURE;
        }

        if (!is_file($file)) {
            $io->error(sprintf('Fichier introuvable : %s', $file));

            return Command::FAILURE;
        }

        if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'xlsx') {
            $io->error('Le fichier doit être au format .xlsx.');

            return Command::FAILURE;
        }

        try {
            [$headers, $rows] = $this->readXlsx($file);
        } catch (\Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        if ($headers === []) {
            $io->error('Aucun en-tête détecté dans le fichier.');

            return Command::FAILURE;
        }

        $fieldMap = $this->buildFieldMap($headers);
        $ignoredHeaders = $this->getIgnoredHeaders($headers, $fieldMap);

        $io->title('Import clients');
        $io->listing([
            sprintf('Fichier : %s', $file),
            sprintf('Lignes détectées : %d', count($rows)),
            sprintf('Mode : %s', $dryRun ? 'test sans enregistrement' : 'import réel'),
        ]);

        if ($fieldMap === []) {
            $io->warning('Aucune colonne du fichier ne correspond aux champs client du site.');

            return Command::SUCCESS;
        }

        $io->section('Colonnes prises en compte');
        $io->table(['Champ site', 'Colonne fichier'], array_map(
            static fn (string $field, string $header): array => [$field, $header],
            array_keys($fieldMap),
            array_values($fieldMap)
        ));

        if ($ignoredHeaders !== []) {
            $io->section('Colonnes ignorées');
            $io->text(implode(', ', $ignoredHeaders));
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $generatedEmails = 0;
        $processedSinceFlush = 0;

        foreach ($rows as $lineNumber => $row) {
            try {
                $data = $this->extractClientData($row, $fieldMap);

                $emailFromFile = $this->normalizeEmail($data['email'] ?? null);
                $customerCode = $this->normalizeText($data['customerCode'] ?? null);
                $fullName = $this->buildFullName($row, $data);
                $user = $this->findExistingClient($emailFromFile, $customerCode);
                $isNew = !$user instanceof User;

                if ($user instanceof User && in_array('ROLE_ADMIN', $user->getRoles(), true)) {
                    $skipped++;
                    $errors[] = sprintf('Ligne %d : compte admin ignoré.', $lineNumber);
                    continue;
                }

                if (!$isNew && $noUpdate) {
                    $skipped++;
                    continue;
                }

                if ($isNew) {
                    $user = new User();
                    $user->setRoles(['ROLE_USER']);
                    $user->setIsActive(true);
                }

                $email = $emailFromFile;
                if ($email === null) {
                    if (!$isNew && $user->getEmail()) {
                        $email = $user->getEmail();
                    } elseif ($skipMissingEmail) {
                        $skipped++;
                        continue;
                    } else {
                        $email = $this->buildGeneratedEmail($fullName, $customerCode, $lineNumber, $generatedEmailDomain);
                        $generatedEmails++;
                    }
                }

                if ($email === null) {
                    $skipped++;
                    continue;
                }

                $user->setEmail($email);

                if (array_key_exists('companyName', $data)) {
                    $user->setCompanyName($this->normalizeText($data['companyName']));
                }

                if (array_key_exists('customerCode', $data)) {
                    $user->setCustomerCode($customerCode);
                }

                if (array_key_exists('address1', $data)) {
                    $user->setAddress1($this->normalizeText($data['address1']));
                }

                if (array_key_exists('address2', $data)) {
                    $user->setAddress2($this->normalizeText($data['address2']));
                }

                if (array_key_exists('city', $data)) {
                    $user->setCity($this->normalizeText($data['city']));
                }

                if (array_key_exists('isActive', $data)) {
                    $user->setIsActive($this->normalizeBool($data['isActive']));
                }

                $plainPassword = $this->normalizeText($data['password'] ?? null);
                if (!$dryRun && ($isNew || $plainPassword !== null)) {
                    $plainPassword ??= $defaultPassword ?: bin2hex(random_bytes(8));
                    $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
                } elseif ($dryRun && $isNew) {
                    $user->setPassword('dry-run-password');
                }

                if (!$dryRun) {
                    $this->entityManager->persist($user);
                    $processedSinceFlush++;

                    if ($processedSinceFlush >= $batchSize) {
                        $this->entityManager->flush();
                        $processedSinceFlush = 0;
                    }
                }

                if ($isNew) {
                    $created++;
                } else {
                    $updated++;
                }
            } catch (\Throwable $exception) {
                $skipped++;
                $errors[] = sprintf('Ligne %d : %s', $lineNumber, $exception->getMessage());
                $this->entityManager->clear();
                $processedSinceFlush = 0;
            }
        }

        if (!$dryRun && $processedSinceFlush > 0) {
            $this->entityManager->flush();
        }

        $io->success($dryRun ? 'Analyse terminée.' : 'Import terminé.');
        $io->table(['Créés', 'Mis à jour', 'Ignorés', 'Emails techniques'], [[
            $created,
            $updated,
            $skipped,
            $generatedEmails,
        ]]);

        if ($errors !== []) {
            $io->warning('Certaines lignes ont été ignorées :');
            foreach (array_slice($errors, 0, 20) as $error) {
                $io->text($error);
            }

            if (count($errors) > 20) {
                $io->text(sprintf('... %d erreur(s) supplémentaire(s).', count($errors) - 20));
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @return array{0: string[], 1: array<int, array<string, mixed>>}
     */
    private function readXlsx(string $file): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('L’extension PHP zip est obligatoire pour lire un fichier .xlsx.');
        }

        $zip = new ZipArchive();
        if ($zip->open($file) !== true) {
            throw new RuntimeException('Impossible d’ouvrir le fichier XLSX.');
        }

        try {
            $sharedStrings = $this->readSharedStrings($zip);
            $sheetPath = $this->resolveFirstSheetPath($zip);
            $sheetXml = $zip->getFromName($sheetPath);

            if ($sheetXml === false) {
                throw new RuntimeException(sprintf('Feuille XLSX introuvable : %s', $sheetPath));
            }

            $sheet = simplexml_load_string($sheetXml);
            if ($sheet === false) {
                throw new RuntimeException('Impossible de lire la première feuille XLSX.');
            }

            $rawRows = [];
            foreach ($sheet->sheetData->row as $rowNode) {
                $rowIndex = (int) $rowNode['r'];
                $cells = [];

                foreach ($rowNode->c as $cellNode) {
                    $reference = (string) $cellNode['r'];
                    $columnIndex = $this->columnIndexFromCellReference($reference);
                    $cells[$columnIndex] = $this->readCellValue($cellNode, $sharedStrings);
                }

                ksort($cells);
                $rawRows[$rowIndex] = $cells;
            }

            if ($rawRows === []) {
                return [[], []];
            }

            ksort($rawRows);
            $headerRowIndex = array_key_first($rawRows);
            $headers = [];
            foreach ($rawRows[$headerRowIndex] as $columnIndex => $value) {
                $headers[$columnIndex] = $this->normalizeHeader((string) $value);
            }

            $rows = [];
            foreach ($rawRows as $rowIndex => $cells) {
                if ($rowIndex === $headerRowIndex) {
                    continue;
                }

                $assoc = [];
                foreach ($headers as $columnIndex => $header) {
                    if ($header === '') {
                        continue;
                    }

                    $assoc[$header] = $cells[$columnIndex] ?? null;
                }

                if ($this->rowHasData($assoc)) {
                    $rows[$rowIndex] = $assoc;
                }
            }

            return [array_values($headers), $rows];
        } finally {
            $zip->close();
        }
    }

    /**
     * @return string[]
     */
    private function readSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $sharedStrings = [];
        $shared = simplexml_load_string($xml);
        if ($shared === false) {
            return [];
        }

        foreach ($shared->si as $stringItem) {
            if (isset($stringItem->t)) {
                $sharedStrings[] = (string) $stringItem->t;
                continue;
            }

            $value = '';
            foreach ($stringItem->r as $run) {
                $value .= (string) $run->t;
            }
            $sharedStrings[] = $value;
        }

        return $sharedStrings;
    }

    private function resolveFirstSheetPath(ZipArchive $zip): string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml === false || $relsXml === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $workbook = simplexml_load_string($workbookXml);
        $rels = simplexml_load_string($relsXml);
        if ($workbook === false || $rels === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $workbookNamespaces = $workbook->getNamespaces(true);
        $relationshipNamespace = $workbookNamespaces['r'] ?? 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
        $firstSheet = $workbook->sheets->sheet[0] ?? null;
        if ($firstSheet === null) {
            return 'xl/worksheets/sheet1.xml';
        }

        $attributes = $firstSheet->attributes($relationshipNamespace);
        $relationshipId = (string) ($attributes['id'] ?? '');
        if ($relationshipId === '') {
            return 'xl/worksheets/sheet1.xml';
        }

        foreach ($rels->Relationship as $relationship) {
            if ((string) $relationship['Id'] !== $relationshipId) {
                continue;
            }

            $target = (string) $relationship['Target'];
            if (str_starts_with($target, '/')) {
                return ltrim($target, '/');
            }

            return 'xl/' . ltrim($target, '/');
        }

        return 'xl/worksheets/sheet1.xml';
    }

    /**
     * @param array<int, string> $sharedStrings
     */
    private function readCellValue(\SimpleXMLElement $cellNode, array $sharedStrings): mixed
    {
        $type = (string) $cellNode['t'];

        if ($type === 'inlineStr') {
            return isset($cellNode->is->t) ? (string) $cellNode->is->t : null;
        }

        $value = isset($cellNode->v) ? (string) $cellNode->v : null;
        if ($value === null || $value === '') {
            return null;
        }

        if ($type === 's') {
            return $sharedStrings[(int) $value] ?? null;
        }

        if ($type === 'b') {
            return $value === '1';
        }

        return $value;
    }

    private function columnIndexFromCellReference(string $reference): int
    {
        preg_match('/^[A-Z]+/i', $reference, $matches);
        $letters = strtoupper($matches[0] ?? 'A');
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }

    /**
     * @param string[] $headers
     * @return array<string, string>
     */
    private function buildFieldMap(array $headers): array
    {
        $map = [];
        $normalizedHeaders = [];

        foreach ($headers as $header) {
            $normalizedHeaders[$header] = $this->normalizeComparableHeader($header);
        }

        foreach (self::FIELD_ALIASES as $field => $aliases) {
            $normalizedAliases = array_map(fn (string $alias): string => $this->normalizeComparableHeader($alias), $aliases);

            foreach ($normalizedHeaders as $header => $normalizedHeader) {
                if (in_array($normalizedHeader, $normalizedAliases, true)) {
                    $map[$field] = $header;
                    break;
                }
            }
        }

        return $map;
    }

    /**
     * @param string[] $headers
     * @param array<string, string> $fieldMap
     * @return string[]
     */
    private function getIgnoredHeaders(array $headers, array $fieldMap): array
    {
        $usedHeaders = array_values($fieldMap);

        return array_values(array_filter($headers, static fn (string $header): bool => $header !== '' && !in_array($header, $usedHeaders, true)));
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, string> $fieldMap
     * @return array<string, mixed>
     */
    private function extractClientData(array $row, array $fieldMap): array
    {
        $data = [];

        foreach ($fieldMap as $field => $header) {
            $data[$field] = $row[$header] ?? null;
        }

        return $data;
    }

    private function findExistingClient(?string $email, ?string $customerCode): ?User
    {
        if ($email !== null) {
            $user = $this->userRepository->findOneBy(['email' => $email]);
            if ($user instanceof User) {
                return $user;
            }
        }

        if ($customerCode !== null) {
            $user = $this->userRepository->findOneBy(['customerCode' => $customerCode]);
            if ($user instanceof User) {
                return $user;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $data
     */
    private function buildFullName(array $row, array $data): ?string
    {
        $parts = [];

        $companyName = $this->normalizeText($data['companyName'] ?? null);
        if ($companyName !== null) {
            $parts[] = $companyName;
        }

        foreach (['prenom', 'prénom', 'first_name', 'firstname'] as $key) {
            $value = $this->normalizeText($row[$key] ?? null);

            if ($value !== null && !in_array(mb_strtolower($value), array_map('mb_strtolower', $parts), true)) {
                $parts[] = $value;
                break;
            }
        }

        if ($parts === []) {
            return null;
        }

        return implode(' ', $parts);
    }

    private function buildGeneratedEmail(?string $fullName, ?string $customerCode, int $lineNumber, string $domain): string
    {
        $source = $fullName ?: ($customerCode ?: 'ligne-' . $lineNumber);
        $slug = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $source));
        $slug = trim($slug, '-');

        if ($slug === '') {
            $slug = 'ligne-' . $lineNumber;
        }

        return sprintf('%s@%s', $slug, $domain);
    }

    private function normalizeHeader(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    private function normalizeComparableHeader(string $value): string
    {
        $value = strtr($this->normalizeHeader($value), [
            'à' => 'a',
            'á' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'ä' => 'a',
            'ç' => 'c',
            'è' => 'e',
            'é' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'î' => 'i',
            'ï' => 'i',
            'ô' => 'o',
            'ö' => 'o',
            'ù' => 'u',
            'û' => 'u',
            'ü' => 'u',
        ]);

        return (string) preg_replace('/[^a-z0-9]+/', '', $value);
    }

    private function normalizeText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        $value = trim((string) $value);

        if ($value === '' || mb_strtolower($value) === 'null') {
            return null;
        }

        if (preg_match('/^\d+\.0$/', $value) === 1) {
            $value = substr($value, 0, -2);
        }

        return $value;
    }

    private function normalizeEmail(mixed $value): ?string
    {
        $email = $this->normalizeText($value);
        if ($email === null) {
            return null;
        }

        $email = mb_strtolower($email);

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function normalizeEmailDomain(string $value): ?string
    {
        $domain = mb_strtolower(trim($value));
        $domain = preg_replace('/^@+/', '', $domain);

        if (!is_string($domain) || $domain === '') {
            return null;
        }

        return filter_var('test@' . $domain, FILTER_VALIDATE_EMAIL) ? $domain : null;
    }

    private function normalizeBool(mixed $value): ?bool
    {
        $value = $this->normalizeText($value);
        if ($value === null) {
            return null;
        }

        return in_array(mb_strtolower($value), ['1', 'true', 'oui', 'yes', 'y', 'actif', 'active'], true);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function rowHasData(array $row): bool
    {
        foreach ($row as $value) {
            if ($this->normalizeText($value) !== null) {
                return true;
            }
        }

        return false;
    }
}
