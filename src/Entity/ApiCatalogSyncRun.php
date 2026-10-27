<?php

namespace App\Entity;

use App\Repository\ApiCatalogSyncRunRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApiCatalogSyncRunRepository::class)]
#[ORM\Table(name: 'api_catalog_sync_run')]
#[ORM\Index(name: 'idx_api_catalog_sync_run_started', columns: ['started_at'])]
class ApiCatalogSyncRun
{
    public const STATUS_RUNNING = 'RUNNING';
    public const STATUS_DONE = 'DONE';
    public const STATUS_ERROR = 'ERROR';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'connection_id', nullable: false, onDelete: 'CASCADE')]
    private ApiReceptionConnection $connection;

    #[ORM\Column(length: 12)]
    private string $status = self::STATUS_RUNNING;

    #[ORM\Column(name: 'remote_total')]
    private int $remoteTotal = 0;

    #[ORM\Column(name: 'received_rows')]
    private int $receivedRows = 0;

    #[ORM\Column(name: 'created_rows')]
    private int $createdRows = 0;

    #[ORM\Column(name: 'updated_rows')]
    private int $updatedRows = 0;

    #[ORM\Column(name: 'unchanged_rows')]
    private int $unchangedRows = 0;

    #[ORM\Column(name: 'removed_rows')]
    private int $removedRows = 0;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $message = null;

    #[ORM\Column(name: 'started_at')]
    private \DateTimeImmutable $startedAt;

    #[ORM\Column(name: 'finished_at', nullable: true)]
    private ?\DateTimeImmutable $finishedAt = null;

    /**
     * Etat de reprise réservé au traitement par lots : page suivante,
     * catégories déjà chargées et identifiants du snapshot reçu.
     *
     * @var array<string, mixed>
     */
    #[ORM\Column(name: 'progress_data', type: 'json')]
    private array $progressData = [];

    public function __construct(ApiReceptionConnection $connection)
    {
        $this->connection = $connection;
        $this->startedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getConnection(): ApiReceptionConnection
    {
        return $this->connection;
    }

    /** @return array<string, mixed> */
    public function getProgressData(): array
    {
        return $this->progressData;
    }

    /** @param array<string, mixed> $progressData */
    public function setProgressData(array $progressData): self
    {
        $this->progressData = $progressData;

        return $this;
    }

    public function clearProgressData(): self
    {
        $this->progressData = [];

        return $this;
    }

    public function isStale(int $timeoutSeconds, ?\DateTimeImmutable $now = null): bool
    {
        if ($this->status !== self::STATUS_RUNNING) {
            return false;
        }

        $threshold = ($now ?? new \DateTimeImmutable())->modify(sprintf('-%d seconds', max(1, $timeoutSeconds)));

        return $this->startedAt <= $threshold;
    }

    public function getRemoteTotal(): int
    {
        return $this->remoteTotal;
    }

    public function setRemoteTotal(int $remoteTotal): self
    {
        $this->remoteTotal = max(0, $remoteTotal);

        return $this;
    }

    public function addReceivedRows(int $count): self
    {
        $this->receivedRows += $count;

        return $this;
    }

    public function addCreatedRows(int $count): self
    {
        $this->createdRows += $count;

        return $this;
    }

    public function addUpdatedRows(int $count): self
    {
        $this->updatedRows += $count;

        return $this;
    }

    public function addUnchangedRows(int $count): self
    {
        $this->unchangedRows += $count;

        return $this;
    }

    public function setRemovedRows(int $count): self
    {
        $this->removedRows = max(0, $count);

        return $this;
    }

    public function addRemovedRows(int $count): self
    {
        $this->removedRows += max(0, $count);

        return $this;
    }

    public function complete(string $message): self
    {
        $this->status = self::STATUS_DONE;
        $this->message = $message;
        $this->finishedAt = new \DateTimeImmutable();

        return $this;
    }

    public function fail(string $message): self
    {
        $this->status = self::STATUS_ERROR;
        $this->message = $message;
        $this->finishedAt = new \DateTimeImmutable();

        return $this;
    }

    /** @return array<string, int|string|null> */
    public function toSummary(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'remoteTotal' => $this->remoteTotal,
            'receivedRows' => $this->receivedRows,
            'createdRows' => $this->createdRows,
            'updatedRows' => $this->updatedRows,
            'unchangedRows' => $this->unchangedRows,
            'removedRows' => $this->removedRows,
            'message' => $this->message,
            'startedAt' => $this->startedAt->format(DATE_ATOM),
            'finishedAt' => $this->finishedAt?->format(DATE_ATOM),
        ];
    }
}
