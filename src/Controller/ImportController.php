<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\ImportJob;
use App\Entity\ImportSource;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ImportController extends AbstractController
{
    public function __construct(
        private readonly CsrfTokenManagerInterface $csrfTokenManager
    ) {
    }

    #[Route('/admin/import', name: 'import_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $source = $em->getRepository(ImportSource::class)->findOneBy([]);
        $csrf = $this->csrfTokenManager->getToken('import_run')->getValue();

        return $this->render('import/index.html.twig', [
            'source' => $source,
            'csrf' => $csrf,
        ]);
    }

    #[Route('/admin/import/start', name: 'import_start', methods: ['POST'])]
    public function start(
        Request $request,
        EntityManagerInterface $em,
        HttpClientInterface $httpClient,
    ): JsonResponse {
        $data = json_decode((string) $request->getContent(), true);

        if (!is_array($data) || !isset($data['_token']) || !$this->isCsrfTokenValid('import_run', $data['_token'])) {
            return $this->json(['ok' => false, 'message' => 'CSRF invalide'], 403);
        }

        /** @var ImportSource|null $source */
        $source = $em->getRepository(ImportSource::class)->findOneBy([]);
        if (!$source || !$source->getDirectoryPath() || !$source->getFileName()) {
            return $this->json(['ok' => false, 'message' => 'Configure d’abord le chemin + nom du fichier.'], 400);
        }

        $pathOrUrl = $source->getFullPath();

        try {
            $path = $this->isUrl($pathOrUrl)
                ? $this->downloadUrlToLocal($pathOrUrl, $httpClient)
                : $pathOrUrl;
        } catch (\Throwable $e) {
            return $this->json([
                'ok' => false,
                'message' => 'Erreur import: ' . $e->getMessage(),
                'pathOrUrl' => $pathOrUrl,
            ], 500);
        }

        if (!is_file($path)) {
            return $this->json(['ok' => false, 'message' => "Fichier introuvable: $path"], 400);
        }

        $h = fopen($path, 'r');
        if (!$h) {
            return $this->json(['ok' => false, 'message' => "Impossible d’ouvrir le fichier: $path"], 400);
        }

        $header = fgetcsv($h, 0, ';', '"', '\\');
        if (!$header) {
            fclose($h);
            return $this->json(['ok' => false, 'message' => 'CSV invalide (entête manquante).'], 400);
        }

        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
        $map = array_flip($header);

        foreach (['Id', 'UniqueId'] as $required) {
            if (!array_key_exists($required, $map)) {
                fclose($h);
                return $this->json(['ok' => false, 'message' => "Colonne manquante dans le CSV: $required"], 400);
            }
        }

        $offsetAfterHeader = ftell($h);
        fclose($h);

        $totalRows = $this->countCsvRows($path);

        $job = new ImportJob($path);
        $job->setStatus(ImportJob::STATUS_RUNNING);
        $job->setTotalRows($totalRows);
        $job->setProcessedRows(0);
        $job->setByteOffset((int) $offsetAfterHeader);
        $job->setInsertedRows(0);
        $job->setUpdatedRows(0);
        $job->setSkippedRows(0);
        $job->setLastMessage('Démarrage import...');

        $em->persist($job);
        $em->flush();

        if ($totalRows === 0) {
            $job->setStatus(ImportJob::STATUS_DONE);
            $job->setFinishedAt(new \DateTimeImmutable('now'));
            $job->setLastMessage('Aucune ligne à importer.');
            $em->flush();

            return $this->json(['ok' => true, 'done' => true] + $this->payload($job));
        }

        return $this->json(['ok' => true, 'done' => false] + $this->payload($job));
    }

    #[Route('/admin/import/step/{id}', name: 'import_step', methods: ['POST'])]
    public function step(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode((string) $request->getContent(), true);

        if (!is_array($data) || !isset($data['_token']) || !$this->isCsrfTokenValid('import_run', $data['_token'])) {
            return $this->json(['ok' => false, 'message' => 'CSRF invalide'], 403);
        }

        $limit = max(50, min(1500, (int) ($data['limit'] ?? 300)));

        /** @var ImportJob|null $job */
        $job = $em->getRepository(ImportJob::class)->find($id);
        if (!$job) {
            return $this->json(['ok' => false, 'message' => 'Job introuvable'], 404);
        }

        if ($job->getStatus() === ImportJob::STATUS_DONE) {
            return $this->json(['ok' => true, 'done' => true] + $this->payload($job));
        }

        if ($job->getStatus() === ImportJob::STATUS_ERROR) {
            return $this->json(['ok' => false, 'done' => true] + $this->payload($job), 500);
        }

        try {
            $result = $this->importChunk($job, $em, $limit);

            $fileSize = @filesize($job->getFilePath()) ?: 0;
            $done =
                $result['eof'] === true
                || $job->getProcessedRows() >= $job->getTotalRows()
                || ($fileSize > 0 && $job->getByteOffset() >= $fileSize);

            $job->setLastMessage(
                "Lu {$job->getProcessedRows()}/{$job->getTotalRows()} | "
                . "INS {$job->getInsertedRows()} | UPD {$job->getUpdatedRows()} | SKIP {$job->getSkippedRows()}"
            );

            if ($done) {
                $job->setStatus(ImportJob::STATUS_DONE);
                $job->setFinishedAt(new \DateTimeImmutable('now'));
            }

            $em->flush();

            return $this->json(['ok' => true, 'done' => $job->getStatus() === ImportJob::STATUS_DONE] + $this->payload($job));
        } catch (\Throwable $e) {
            $job->setStatus(ImportJob::STATUS_ERROR);
            $job->setLastMessage('Erreur: ' . $e->getMessage());
            $job->setFinishedAt(new \DateTimeImmutable('now'));
            $em->flush();

            return $this->json(['ok' => false, 'done' => true] + $this->payload($job), 500);
        }
    }

    private function importChunk(ImportJob $job, EntityManagerInterface $em, int $limit): array
    {
        $path = $job->getFilePath();
        $h = fopen($path, 'r');
        if (!$h) {
            throw new \RuntimeException("Impossible d’ouvrir: $path");
        }

        $header = fgetcsv($h, 0, ';', '"', '\\');
        if (!$header) {
            fclose($h);
            throw new \RuntimeException('Entête CSV manquante');
        }

        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
        $map = array_flip($header);

        foreach (['Id', 'UniqueId'] as $required) {
            if (!array_key_exists($required, $map)) {
                fclose($h);
                throw new \RuntimeException("Colonne manquante: $required");
            }
        }

        fseek($h, $job->getByteOffset());

        /** @var ArticleRepository $articleRepo */
        $articleRepo = $em->getRepository(Article::class);

        $articlesByCode = [];
        $read = 0;

        while ($read < $limit && ($row = fgetcsv($h, 0, ';', '"', '\\')) !== false) {
            $read++;
            $job->setProcessedRows($job->getProcessedRows() + 1);

            $uniqueId = trim((string) ($row[$map['UniqueId']] ?? ''));
            $code = trim((string) ($row[$map['Id']] ?? ''));

            if ($uniqueId === '' || $code === '') {
                $job->addSkipped(1);
                continue;
            }

            /** @var Article|null $article */
            $article = $articleRepo->find($uniqueId);

            if (!$article && isset($articlesByCode[$code])) {
                $article = $articlesByCode[$code];
            }

            if (!$article) {
                $article = $articleRepo->findOneBy(['id' => $code]);
            }

            $new = $this->buildNormalizedRow($row, $map);

            if (!$article) {
                $article = new Article();
                $article->setUniqueId($uniqueId);
                $this->applyRow($article, $new);
                $em->persist($article);
                $job->addInserted(1);
            } else {
                if ($this->isSameAsDb($article, $new)) {
                    $job->addSkipped(1);
                } else {
                    $this->applyRow($article, $new);
                    $job->addUpdated(1);
                }
            }

            $articlesByCode[$code] = $article;

            if (($job->getProcessedRows() % 400) === 0) {
                $em->flush();
                $em->clear(Article::class);
                $articlesByCode = [];
            }

            if ($job->getProcessedRows() >= $job->getTotalRows()) {
                break;
            }
        }

        $em->flush();

        $job->setByteOffset((int) ftell($h));
        $eof = feof($h);
        fclose($h);

        if ($job->getProcessedRows() > $job->getTotalRows()) {
            $job->setProcessedRows($job->getTotalRows());
        }

        return ['read' => $read, 'eof' => $eof];
    }

    private function buildNormalizedRow(array $row, array $map): array
    {
        $get = function (string $k) use ($row, $map): string {
            if (!array_key_exists($k, $map)) {
                return '';
            }

            return trim((string) ($row[$map[$k]] ?? ''));
        };

        $js = fn(string $k) => $this->parseJsDate($get($k));
        $sql = fn(string $k) => $this->parseSqlDate($get($k));
        $dec = fn(string $k) => $this->toDecimal($get($k));

        return [
            'Id' => $get('Id'),
            'UniqueId' => $get('UniqueId'),
            'DesComClear' => $get('DesComClear'),
            'RealStock' => (int) ($get('RealStock') === '' ? 0 : $get('RealStock')),
            'SalePriceVatIncluded' => $dec('SalePriceVatIncluded'),
            'SalePriceVatExcluded' => $dec('SalePriceVatExcluded'),
            'xx_Position' => $get('xx_Position'),
            'xx_Annee' => $get('xx_Annee'),
            'xx_Vehicule' => $get('xx_Vehicule'),
            'xx_Marque' => $get('xx_Marque'),
            'sysModifiedDate' => $js('sysModifiedDate'),
            'sysCreatedDate' => $js('sysCreatedDate'),
            'FamilyName' => $get('FamilyName'),
            'ExportStartedAt' => $sql('ExportStartedAt'),
            'FtpFinishedAt' => $sql('FtpFinishedAt'),
        ];
    }

    private function isSameAsDb(Article $a, array $n): bool
    {
        if (($a->getId() ?? '') !== $n['Id']) {
            return false;
        }
        if (($a->getDesComClear() ?? '') !== $n['DesComClear']) {
            return false;
        }
        if ($a->getRealStock() !== $n['RealStock']) {
            return false;
        }
        if (($a->getXxPosition() ?? '') !== $n['xx_Position']) {
            return false;
        }
        if ($this->toDecimal($a->getSalePriceVatIncluded()) !== $n['SalePriceVatIncluded']) {
            return false;
        }
        if ($this->toDecimal($a->getSalePriceVatExcluded()) !== $n['SalePriceVatExcluded']) {
            return false;
        }
        if (($a->getXxAnnee() ?? '') !== $n['xx_Annee']) {
            return false;
        }
        if (($a->getXxVehicule() ?? '') !== $n['xx_Vehicule']) {
            return false;
        }
        if (($a->getXxMarque() ?? '') !== $n['xx_Marque']) {
            return false;
        }
        if (($a->getFamilyName() ?? '') !== $n['FamilyName']) {
            return false;
        }
        if (!$this->sameDate($a->getSysModifiedDate(), $n['sysModifiedDate'])) {
            return false;
        }
        if (!$this->sameDate($a->getSysCreatedDate(), $n['sysCreatedDate'])) {
            return false;
        }
        if (!$this->sameDate($a->getExportStartedAt(), $n['ExportStartedAt'])) {
            return false;
        }
        if (!$this->sameDate($a->getFtpFinishedAt(), $n['FtpFinishedAt'])) {
            return false;
        }

        return true;
    }

    private function applyRow(Article $a, array $n): void
    {
        $a->setId($n['Id']);
        $a->setDesComClear($n['DesComClear']);
        $a->setRealStock($n['RealStock']);
        $a->setSalePriceVatIncluded($n['SalePriceVatIncluded']);
        $a->setSalePriceVatExcluded($n['SalePriceVatExcluded']);
        $a->setXxPosition($n['xx_Position'] !== '' ? $n['xx_Position'] : null);
        $a->setXxAnnee($n['xx_Annee'] !== '' ? $n['xx_Annee'] : null);
        $a->setXxVehicule($n['xx_Vehicule'] !== '' ? $n['xx_Vehicule'] : null);
        $a->setXxMarque($n['xx_Marque'] !== '' ? $n['xx_Marque'] : null);
        $a->setSysModifiedDate($n['sysModifiedDate']);
        $a->setSysCreatedDate($n['sysCreatedDate']);
        $a->setFamilyName($n['FamilyName'] !== '' ? $n['FamilyName'] : null);
        $a->setExportStartedAt($n['ExportStartedAt']);
        $a->setFtpFinishedAt($n['FtpFinishedAt']);
    }

    private function sameDate(?\DateTimeImmutable $a, ?\DateTimeImmutable $b): bool
    {
        if ($a === null && $b === null) {
            return true;
        }
        if ($a === null || $b === null) {
            return false;
        }

        return $a->getTimestamp() === $b->getTimestamp();
    }

    private function payload(ImportJob $job): array
    {
        return [
            'jobId' => $job->getId(),
            'status' => $job->getStatus(),
            'totalRows' => $job->getTotalRows(),
            'processedRows' => $job->getProcessedRows(),
            'insertedRows' => $job->getInsertedRows(),
            'updatedRows' => $job->getUpdatedRows(),
            'skippedRows' => $job->getSkippedRows(),
            'message' => $job->getLastMessage(),
        ];
    }

    private function isUrl(string $value): bool
    {
        return (bool) filter_var($value, FILTER_VALIDATE_URL);
    }

    private function downloadUrlToLocal(string $url, HttpClientInterface $httpClient): string
    {
        $projectDir = (string) $this->getParameter('kernel.project_dir');
        $tmpDir = $projectDir . '/var/imports';

        if (!is_dir($tmpDir) && !@mkdir($tmpDir, 0775, true) && !is_dir($tmpDir)) {
            throw new \RuntimeException('Impossible de créer le dossier temporaire: ' . $tmpDir);
        }

        $tmpFile = $tmpDir . '/items_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.csv';

        $response = $httpClient->request('GET', $url, [
            'timeout' => 120,
            'headers' => ['User-Agent' => 'SymfonyImporter/1.0'],
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('HTTP ' . $response->getStatusCode());
        }

        $in = $response->toStream();
        $out = fopen($tmpFile, 'w');
        if (!$out) {
            throw new \RuntimeException('Impossible d’écrire: ' . $tmpFile);
        }

        stream_copy_to_stream($in, $out);
        fclose($out);

        if (!is_file($tmpFile) || filesize($tmpFile) === 0) {
            throw new \RuntimeException('Fichier téléchargé vide');
        }

        return $tmpFile;
    }

    private function countCsvRows(string $path): int
    {
        $h = fopen($path, 'r');
        if (!$h) {
            return 0;
        }

        $header = fgetcsv($h, 0, ';', '"', '\\');
        if (!$header) {
            fclose($h);
            return 0;
        }

        $count = 0;
        while (($row = fgetcsv($h, 0, ';', '"', '\\')) !== false) {
            if (count($row) === 1 && trim((string) $row[0]) === '') {
                continue;
            }
            $count++;
        }

        fclose($h);

        return $count;
    }

    private function toDecimal(string $v): string
    {
        $v = trim(str_replace(',', '.', $v));
        if ($v === '') {
            return '0.00';
        }
        if (!str_contains($v, '.')) {
            return $v . '.00';
        }

        return number_format((float) $v, 2, '.', '');
    }

    private function parseSqlDate(string $s): ?\DateTimeImmutable
    {
        $s = trim($s);
        if ($s === '') {
            return null;
        }

        $dt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $s);

        return $dt ?: null;
    }

    private function parseJsDate(string $s): ?\DateTimeImmutable
    {
        $s = trim($s);
        if ($s === '') {
            return null;
        }

        $s = preg_replace('/\s*\(.*\)\s*$/u', '', $s);
        $s = preg_replace('/GMT([+-]\d{4})/', 'GMT $1', $s);

        $dt = \DateTimeImmutable::createFromFormat('D M d Y H:i:s \G\M\T O', $s);

        return $dt ?: null;
    }
}