<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use SplFileObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/users/import-wordpress', name: 'admin_users_wp_import_')]
class WordPressUserImportController extends AbstractController
{
    #[Route('', name: 'page', methods: ['GET'])]
    public function page(): Response
    {
        return $this->render('admin/user/import_wordpress.html.twig');
    }

    #[Route('/ajax', name: 'ajax', methods: ['POST'])]
    public function ajaxImport(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        try {
            if (!$this->isCsrfTokenValid('wp_user_import', (string) $request->request->get('_token'))) {
                return $this->json([
                    'ok' => false,
                    'message' => 'Token CSRF invalide.',
                ], 400);
            }

            /** @var UploadedFile|null $file */
            $file = $request->files->get('file');

            if (!$file instanceof UploadedFile) {
                return $this->json([
                    'ok' => false,
                    'message' => 'Aucun fichier CSV reçu.',
                    'debug' => [
                        'files_keys' => array_keys($request->files->all()),
                    ],
                ], 400);
            }

            if (!$file->isValid()) {
                return $this->json([
                    'ok' => false,
                    'message' => 'Le fichier uploadé est invalide.',
                    'debug' => [
                        'error' => $file->getError(),
                        'client_name' => $file->getClientOriginalName(),
                    ],
                ], 400);
            }

            $extension = strtolower((string) $file->getClientOriginalExtension());
            if ($extension !== 'csv') {
                return $this->json([
                    'ok' => false,
                    'message' => 'Le fichier doit être un CSV.',
                    'debug' => [
                        'extension' => $extension,
                        'client_name' => $file->getClientOriginalName(),
                    ],
                ], 400);
            }

            [$headers, $rows] = $this->readCsv($file);

            if (empty($headers)) {
                return $this->json([
                    'ok' => false,
                    'message' => 'Impossible de lire les en-têtes du CSV.',
                ], 400);
            }

            $created = 0;
            $updated = 0;
            $skipped = 0;
            $errors = [];

            $nextNumber = $userRepository->findMaxHopicCustomerNumber();

            foreach ($rows as $lineNumber => $row) {
                try {
                    $email = strtolower(trim((string) ($row['user_email'] ?? $row['email'] ?? '')));
                    $legacyPasswordHash = trim((string) ($row['user_pass'] ?? $row['password'] ?? ''));

                    if ($email === '' || $legacyPasswordHash === '') {
                        $skipped++;
                        continue;
                    }

                    $user = $userRepository->findOneBy(['email' => $email]);
                    $isNew = !$user instanceof User;

                    if ($isNew) {
                        $user = new User();
                        $user->setRoles(['ROLE_USER']);
                        $user->setIsActive(true);
                        $user->setCustomerCode(sprintf('HOPIC%05d', ++$nextNumber));
                    }

                    $user->setEmail($email);
                    $user->setPassword($legacyPasswordHash);

                    $companyName = $this->firstNonEmpty($row, [
                        'display_name',
                        'company_name',
                        'companyname',
                        'societe',
                        'company',
                        'user_login',
                    ]);

                    if ($companyName !== null) {
                        $user->setCompanyName($companyName);
                    }

                    $address1 = $this->firstNonEmpty($row, [
                        'address1',
                        'address',
                        'adresse',
                        'billing_address_1',
                        'shipping_address_1',
                    ]);

                    $address2 = $this->firstNonEmpty($row, [
                        'address2',
                        'adresse2',
                        'billing_address_2',
                        'shipping_address_2',
                    ]);

                    $city = $this->firstNonEmpty($row, [
                        'city',
                        'ville',
                        'billing_city',
                        'shipping_city',
                    ]);

                    if ($address1 !== null) {
                        $user->setAddress1($address1);
                    }

                    if ($address2 !== null) {
                        $user->setAddress2($address2);
                    }

                    if ($city !== null) {
                        $user->setCity($city);
                    }

                    if (!$user->getCustomerCode()) {
                        $user->setCustomerCode(sprintf('HOPIC%05d', ++$nextNumber));
                    }

                    $em->persist($user);
                    $em->flush();

                    if ($isNew) {
                        $created++;
                    } else {
                        $updated++;
                    }
                } catch (\Throwable $e) {
                    $errors[] = sprintf('Ligne %d : %s', $lineNumber, $e->getMessage());
                    $em->clear();
                }
            }

            return $this->json([
                'ok' => true,
                'message' => 'Import terminé.',
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => $errors,
                'headers' => $headers,
                'rowsCount' => count($rows),
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'ok' => false,
                'message' => 'Erreur serveur pendant l’import.',
                'debug' => [
                    'exception' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            ], 500);
        }
    }

    /**
     * @return array{0: string[], 1: array<int, array<string, string|null>>}
     */
    private function readCsv(UploadedFile $file): array
    {
        $path = $file->getPathname();
        $lines = file($path);

        if (!$lines || !isset($lines[0])) {
            return [[], []];
        }

        $firstLine = (string) $lines[0];
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $csv = new SplFileObject($path);
        $csv->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
        $csv->setCsvControl($delimiter);

        $headers = [];
        $rows = [];

        foreach ($csv as $index => $line) {
            if (!is_array($line) || $line === [null]) {
                continue;
            }

            if ($index === 0) {
                $headers = array_map(fn ($h) => $this->normalizeHeader((string) $h), $line);

                if (isset($headers[0])) {
                    $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
                }

                continue;
            }

            $assoc = [];
            foreach ($headers as $i => $header) {
                if ($header === '') {
                    continue;
                }

                $assoc[$header] = isset($line[$i]) ? trim((string) $line[$i]) : null;
            }

            if (!empty($assoc)) {
                $rows[$index + 1] = $assoc;
            }
        }

        return [$headers, $rows];
    }

    private function normalizeHeader(string $value): string
    {
        return strtolower(trim($value));
    }

    /**
     * @param array<string, string|null> $row
     * @param string[] $keys
     */
    private function firstNonEmpty(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = trim((string) ($row[$key] ?? ''));

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }
}