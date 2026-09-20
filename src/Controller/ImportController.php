<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\ImportJob;
use App\Entity\ImportSource;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
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
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly LoggerInterface $logger
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

        $runningJob = $this->findRunningJob($em);
        if ($runningJob instanceof ImportJob) {
            if ($runningJob->getStartedAt() < new \DateTimeImmutable('-2 hours')) {
                $runningJob->setStatus(ImportJob::STATUS_ERROR);
                $runningJob->setFinishedAt(new \DateTimeImmutable('now'));
                $runningJob->setLastMessage('Ancien import interrompu automatiquement apres 2 heures. Nouvel import relance.');
                $em->flush();
            } elseif (!is_file($runningJob->getFilePath())) {
                $runningJob->setStatus(ImportJob::STATUS_ERROR);
                $runningJob->setFinishedAt(new \DateTimeImmutable('now'));
                $runningJob->setLastMessage('Ancien import bloque: le fichier temporaire est introuvable. Relance possible.');
                $em->flush();
            } else {
                $csvArticleUniqueIds = $this->readCsvArticleUniqueIds($runningJob->getFilePath());
                $expectedArticleCount = count($csvArticleUniqueIds);
                $duplicateCsvRows = max(0, $runningJob->getTotalRows() - $expectedArticleCount);
                $databaseArticleCount = (int) $em->getRepository(Article::class)->count([]);
                $runningJob->setLastMessage('Import deja en cours. Reprise du job #' . $runningJob->getId() . '.');
                $em->flush();

                return $this->json([
                    'ok' => true,
                    'done' => false,
                    'alreadyRunning' => true,
                ] + $this->payload($runningJob, $databaseArticleCount, $expectedArticleCount, $duplicateCsvRows));
            }
        }

        /** @var ImportSource|null $source */
        $source = $em->getRepository(ImportSource::class)->findOneBy([]);
        if (!$source || !$source->getDirectoryPath() || !$source->getFileName()) {
            return $this->json(['ok' => false, 'message' => "Configure d'abord le chemin + nom du fichier."], 400);
        }

        $pathOrUrl = $source->getFullPath();

        try {
            $path = $this->prepareImportWorkingFile($pathOrUrl, $httpClient);
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
            return $this->json(['ok' => false, 'message' => "Impossible d'ouvrir le fichier: $path"], 400);
        }

        $header = fgetcsv($h, 0, ';', '"', '\\');
        if (!$header) {
            fclose($h);
            return $this->json(['ok' => false, 'message' => 'CSV invalide (entete manquante).'], 400);
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
        $csvArticleUniqueIds = $this->readCsvArticleUniqueIds($path);
        $expectedArticleCount = count($csvArticleUniqueIds);
        $duplicateCsvRows = max(0, $totalRows - $expectedArticleCount);
        $databaseArticleCount = (int) $em->getRepository(Article::class)->count([]);

        $job = new ImportJob($path);
        $job->setStatus(ImportJob::STATUS_RUNNING);
        $job->setTotalRows($totalRows);
        $job->setProcessedRows(0);
        $job->setByteOffset((int) $offsetAfterHeader);
        $job->setInsertedRows(0);
        $job->setUpdatedRows(0);
        $job->setSkippedRows(0);
        $job->setCreatedArticles([]);
        $job->setLastMessage($this->buildStartMessage($expectedArticleCount, $duplicateCsvRows));

        $em->persist($job);
        $em->flush();

        if ($totalRows === 0) {
            $job->setStatus(ImportJob::STATUS_DONE);
            $job->setFinishedAt(new \DateTimeImmutable('now'));
            $job->setLastMessage('Aucune ligne a importer.');
            $em->flush();

            return $this->json(['ok' => true, 'done' => true] + $this->payload($job, $databaseArticleCount, $expectedArticleCount, $duplicateCsvRows));
        }

        return $this->json(['ok' => true, 'done' => false] + $this->payload($job, $databaseArticleCount, $expectedArticleCount, $duplicateCsvRows));
    }

    #[Route('/admin/import/step/{id}', name: 'import_step', methods: ['POST'])]
    public function step(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode((string) $request->getContent(), true);

        if (!is_array($data) || !isset($data['_token']) || !$this->isCsrfTokenValid('import_run', $data['_token'])) {
            return $this->json(['ok' => false, 'message' => 'CSRF invalide'], 403);
        }

        $limit = max(25, min(500, (int) ($data['limit'] ?? 120)));

        /** @var ImportJob|null $job */
        $job = $em->getRepository(ImportJob::class)->find($id);
        if (!$job) {
            return $this->json(['ok' => false, 'message' => 'Job introuvable'], 404);
        }

        if ($job->getStatus() === ImportJob::STATUS_DONE) {
            $csvArticleUniqueIds = $this->readCsvArticleUniqueIds($job->getFilePath());
            $expectedArticleCount = count($csvArticleUniqueIds);
            $duplicateCsvRows = max(0, $job->getTotalRows() - $expectedArticleCount);
            $databaseArticleCount = (int) $em->getRepository(Article::class)->count([]);

            return $this->json(['ok' => true, 'done' => true] + $this->payload($job, $databaseArticleCount, $expectedArticleCount, $duplicateCsvRows));
        }

        if ($job->getStatus() === ImportJob::STATUS_ERROR) {
            return $this->json(['ok' => false, 'done' => true] + $this->payload($job), 500);
        }

        try {
            $startedAt = microtime(true);
            $result = $this->importChunk($job, $em, $limit);
            $elapsedMs = (int) round((microtime(true) - $startedAt) * 1000);

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
                $csvArticleUniqueIds = $this->readCsvArticleUniqueIds($job->getFilePath());
                $expectedArticleCount = count($csvArticleUniqueIds);
                $duplicateCsvRows = max(0, $job->getTotalRows() - $expectedArticleCount);
                $removedArticles = $this->synchronizeArticlesWithCsvUniqueIds($csvArticleUniqueIds, $em);
                $databaseArticleCount = (int) $em->getRepository(Article::class)->count([]);

                $job->setStatus(ImportJob::STATUS_DONE);
                $job->setFinishedAt(new \DateTimeImmutable('now'));
                $job->setLastMessage($this->buildFinalMessage(
                    $expectedArticleCount,
                    $job->getTotalRows(),
                    $duplicateCsvRows,
                    $databaseArticleCount,
                    $removedArticles,
                    $job->getInsertedRows(),
                    $job->getUpdatedRows(),
                    $job->getSkippedRows()
                ));
            }

            $em->flush();

            return $this->json([
                'ok' => true,
                'done' => $job->getStatus() === ImportJob::STATUS_DONE,
                'elapsedMs' => $elapsedMs,
            ] + $this->payload(
                    $job,
                    $databaseArticleCount ?? null,
                    $expectedArticleCount ?? null,
                    $duplicateCsvRows ?? null
                ));
        } catch (\Throwable $e) {
            $this->logger->error('Article import step failed.', [
                'jobId' => $job->getId(),
                'exception' => $e,
            ]);

            $job->setStatus(ImportJob::STATUS_ERROR);
            $job->setLastMessage('Erreur: ' . $e->getMessage());
            $job->setFinishedAt(new \DateTimeImmutable('now'));
            $em->flush();

            $databaseArticleCount = (int) $em->getRepository(Article::class)->count([]);

            return $this->json(['ok' => false, 'done' => true] + $this->payload($job, $databaseArticleCount), 500);
        }
    }

    #[Route('/admin/import/stop/{id}', name: 'import_stop', methods: ['POST'])]
    public function stop(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode((string) $request->getContent(), true);

        if (!is_array($data) || !isset($data['_token']) || !$this->isCsrfTokenValid('import_run', $data['_token'])) {
            return $this->json(['ok' => false, 'message' => 'CSRF invalide'], 403);
        }

        /** @var ImportJob|null $job */
        $job = $em->getRepository(ImportJob::class)->find($id);
        if (!$job) {
            return $this->json(['ok' => false, 'message' => 'Job introuvable'], 404);
        }

        if ($job->getStatus() === ImportJob::STATUS_RUNNING) {
            $job->setStatus(ImportJob::STATUS_ERROR);
            $job->setFinishedAt(new \DateTimeImmutable('now'));
            $job->setLastMessage('Import arrete par l utilisateur.');
            $em->flush();
        }

        $databaseArticleCount = (int) $em->getRepository(Article::class)->count([]);

        return $this->json(['ok' => true, 'done' => true] + $this->payload($job, $databaseArticleCount));
    }

    private function importChunk(ImportJob $job, EntityManagerInterface $em, int $limit): array
    {
        $path = $job->getFilePath();
        $h = fopen($path, 'r');
        if (!$h) {
            throw new \RuntimeException("Impossible d'ouvrir: $path");
        }

        $header = fgetcsv($h, 0, ';', '"', '\\');
        if (!$header) {
            fclose($h);
            throw new \RuntimeException('Entete CSV manquante');
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

        $normalizedRows = [];
        $uniqueIds = [];
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

            $new = $this->buildNormalizedRow($row, $map);
            $normalizedRows[] = $new;
            $uniqueIds[$uniqueId] = true;

            if ($job->getProcessedRows() >= $job->getTotalRows()) {
                break;
            }
        }

        $job->setByteOffset((int) ftell($h));
        $eof = feof($h);
        fclose($h);

        if ($job->getProcessedRows() > $job->getTotalRows()) {
            $job->setProcessedRows($job->getTotalRows());
        }

        if ($normalizedRows === []) {
            $em->flush();

            return ['read' => $read, 'eof' => $eof];
        }

        /** @var ArticleRepository $articleRepo */
        $articleRepo = $em->getRepository(Article::class);

        /** @var Article[] $existingArticles */
        $existingArticles = $articleRepo->createQueryBuilder('a')
            ->andWhere('a.uniqueId IN (:uniqueIds)')
            ->setParameter('uniqueIds', array_keys($uniqueIds))
            ->getQuery()
            ->getResult();

        $existingByUniqueId = [];
        foreach ($existingArticles as $article) {
            if ($article->getUniqueId()) {
                $existingByUniqueId[$article->getUniqueId()] = $article;
            }
        }

        foreach ($normalizedRows as $new) {
            $article = $existingByUniqueId[$new['UniqueId']] ?? null;

            if (!$article instanceof Article) {
                $article = new Article();
                $article->setUniqueId($new['UniqueId']);
                $this->applyRow($article, $new);
                $em->persist($article);
                $job->addInserted(1);
                $job->addCreatedArticle([
                    'uniqueId' => $new['UniqueId'],
                    'code' => $new['Id'],
                    'designation' => $new['DesComClear'],
                    'stock' => $new['RealStock'],
                    'family' => $new['FamilyName'],
                    'brand' => $new['xx_Marque'],
                ]);
            } else {
                if ($this->isSameAsDb($article, $new)) {
                    $job->addSkipped(1);
                } else {
                    $this->applyRow($article, $new);
                    $job->addUpdated(1);
                }
            }

            if ($article->getUniqueId()) {
                $existingByUniqueId[$article->getUniqueId()] = $article;
            }
        }

        $em->flush();
        $em->clear(Article::class);

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
            'reduction' => $get('reduction') !== '' ? $get('reduction') : $get('Reduction'),
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
        if (($a->getReduction() ?? '') !== $n['reduction']) {
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
        $a->setReduction($n['reduction'] !== '' ? $n['reduction'] : null);
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

    private function payload(
        ImportJob $job,
        ?int $databaseArticleCount = null,
        ?int $expectedArticleCount = null,
        ?int $duplicateCsvRows = null
    ): array
    {
        return [
            'jobId' => $job->getId(),
            'status' => $job->getStatus(),
            'totalRows' => $job->getTotalRows(),
            'expectedArticleCount' => $expectedArticleCount,
            'duplicateCsvRows' => $duplicateCsvRows,
            'databaseArticleCount' => $databaseArticleCount,
            'countsMatch' => $databaseArticleCount !== null && $expectedArticleCount !== null
                ? $databaseArticleCount === $expectedArticleCount
                : null,
            'processedRows' => $job->getProcessedRows(),
            'insertedRows' => $job->getInsertedRows(),
            'updatedRows' => $job->getUpdatedRows(),
            'skippedRows' => $job->getSkippedRows(),
            'message' => $job->getLastMessage(),
            'createdArticles' => $job->getStatus() === ImportJob::STATUS_DONE ? $job->getCreatedArticles() : [],
        ];
    }

    private function findRunningJob(EntityManagerInterface $em): ?ImportJob
    {
        /** @var ImportJob|null $job */
        $job = $em->getRepository(ImportJob::class)
            ->createQueryBuilder('j')
            ->andWhere('j.status = :status')
            ->setParameter('status', ImportJob::STATUS_RUNNING)
            ->orderBy('j.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $job;
    }

    private function buildStartMessage(int $articleCount, int $duplicateCsvRows): string
    {
        $message = sprintf(
            'Vous allez mettre a jour %d article%s unique%s par UniqueId.',
            $articleCount,
            $articleCount > 1 ? 's' : '',
            $articleCount > 1 ? 's' : ''
        );

        if ($duplicateCsvRows > 0) {
            $message .= sprintf(' %d ligne%s avec UniqueId deja present detectee%s dans le CSV.', $duplicateCsvRows, $duplicateCsvRows > 1 ? 's' : '', $duplicateCsvRows > 1 ? 's' : '');
        }

        return $message;
    }

    private function buildFinalMessage(
        int $expectedArticleCount,
        int $csvRows,
        int $duplicateCsvRows,
        int $databaseArticleCount,
        int $removedArticles,
        int $insertedRows,
        int $updatedRows,
        int $skippedRows
    ): string {
        $summary = sprintf(
            'Import termine. CSV: %d articles uniques par UniqueId (%d lignes, %d doublons UniqueId). Base: %d articles. INS %d | UPD %d | SKIP %d | SUP %d.',
            $expectedArticleCount,
            $csvRows,
            $duplicateCsvRows,
            $databaseArticleCount,
            $insertedRows,
            $updatedRows,
            $skippedRows,
            $removedArticles
        );

        if ($databaseArticleCount === $expectedArticleCount) {
            return $summary . ' Comptage conforme.';
        }

        return $summary . ' Attention: le nombre en base ne correspond pas au CSV.';
    }

    /**
     * @param array<string, true> $csvUniqueIds
     */
    private function synchronizeArticlesWithCsvUniqueIds(array $csvUniqueIds, EntityManagerInterface $em): int
    {
        if ($csvUniqueIds === []) {
            return 0;
        }

        $rows = $em->createQueryBuilder()
            ->select('a.uniqueId AS uniqueId')
            ->from(Article::class, 'a')
            ->getQuery()
            ->getArrayResult();

        $removed = 0;
        foreach ($rows as $row) {
            $uniqueId = (string) ($row['uniqueId'] ?? '');
            if ($uniqueId === '' || isset($csvUniqueIds[$uniqueId])) {
                continue;
            }

            $em->remove($em->getReference(Article::class, $uniqueId));
            $removed++;

            if (($removed % 400) === 0) {
                $em->flush();
                $em->clear(Article::class);
            }
        }

        if ($removed > 0) {
            $em->flush();
            $em->clear(Article::class);
        }

        return $removed;
    }

    /**
     * @return array<string, true>
     */
    private function readCsvArticleUniqueIds(string $path): array
    {
        $h = fopen($path, 'r');
        if (!$h) {
            return [];
        }

        $header = fgetcsv($h, 0, ';', '"', '\\');
        if (!$header) {
            fclose($h);
            return [];
        }

        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
        $map = array_flip($header);
        if (!array_key_exists('UniqueId', $map)) {
            fclose($h);
            return [];
        }

        $uniqueIds = [];
        while (($row = fgetcsv($h, 0, ';', '"', '\\')) !== false) {
            if (count($row) === 1 && trim((string) $row[0]) === '') {
                continue;
            }

            $uniqueId = trim((string) ($row[$map['UniqueId']] ?? ''));
            if ($uniqueId !== '') {
                $uniqueIds[$uniqueId] = true;
            }
        }

        fclose($h);

        return $uniqueIds;
    }

    private function isUrl(string $value): bool
    {
        return (bool) filter_var($value, FILTER_VALIDATE_URL);
    }

    private function prepareImportWorkingFile(string $pathOrUrl, HttpClientInterface $httpClient): string
    {
        $projectDir = (string) $this->getParameter('kernel.project_dir');
        $tmpDir = $projectDir . '/var/imports';

        if (!is_dir($tmpDir) && !@mkdir($tmpDir, 0775, true) && !is_dir($tmpDir)) {
            throw new \RuntimeException('Impossible de creer le dossier temporaire: ' . $tmpDir);
        }

        $targetFile = $tmpDir . '/articles-current.csv';
        $tmpFile = $tmpDir . '/articles-current.' . bin2hex(random_bytes(6)) . '.tmp';

        if ($this->isUrl($pathOrUrl)) {
            $response = $httpClient->request('GET', $pathOrUrl, [
                'timeout' => 120,
                'headers' => ['User-Agent' => 'SymfonyImporter/1.0'],
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException('HTTP ' . $response->getStatusCode());
            }

            $in = $response->toStream();
            $out = fopen($tmpFile, 'w');
            if (!$out) {
                throw new \RuntimeException("Impossible d'ecrire: " . $tmpFile);
            }

            stream_copy_to_stream($in, $out);
            fclose($out);
        } else {
            if (!is_file($pathOrUrl)) {
                throw new \RuntimeException('Fichier introuvable: ' . $pathOrUrl);
            }

            $sourceRealPath = realpath($pathOrUrl);
            $targetRealPath = is_file($targetFile) ? realpath($targetFile) : false;

            if ($sourceRealPath !== false && $targetRealPath !== false && $sourceRealPath === $targetRealPath) {
                return $targetFile;
            }

            if (!@copy($pathOrUrl, $tmpFile)) {
                throw new \RuntimeException('Impossible de copier le CSV dans var/imports.');
            }
        }

        if (!is_file($tmpFile) || filesize($tmpFile) === 0) {
            @unlink($tmpFile);
            throw new \RuntimeException('Le fichier CSV recupere est vide.');
        }

        if (is_file($targetFile) && !@unlink($targetFile)) {
            @unlink($tmpFile);
            throw new \RuntimeException('Impossible de remplacer l ancien fichier CSV de travail.');
        }

        if (!@rename($tmpFile, $targetFile)) {
            @unlink($tmpFile);
            throw new \RuntimeException('Impossible de finaliser le fichier CSV de travail.');
        }

        return $targetFile;
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
