<?php

namespace App\Entity;

use App\Repository\EbpInvoiceImportRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EbpInvoiceImportRepository::class)]
#[ORM\Table(name: 'ebp_invoice_import')]
#[ORM\UniqueConstraint(name: 'uniq_ebp_invoice_import_user', columns: ['user_id'])]
class EbpInvoiceImport
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(name: 'remote_request_id', nullable: true)]
    private ?int $remoteRequestId = null;

    #[ORM\Column(length: 16)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(name: 'invoice_count')]
    private int $invoiceCount = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(name: 'requested_at')]
    private \DateTimeImmutable $requestedAt;

    #[ORM\Column(name: 'updated_at')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(name: 'completed_at', nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(name: 'last_synced_at', nullable: true)]
    private ?\DateTimeImmutable $lastSyncedAt = null;

    #[ORM\Column(name: 'period_from', type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $periodFrom = null;

    #[ORM\Column(name: 'period_to', type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $periodTo = null;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->requestedAt = new \DateTimeImmutable();
        $this->updatedAt = $this->requestedAt;
    }

    /** @param array<string, mixed> $remote */
    public function request(array $remote): void
    {
        $this->remoteRequestId = is_int($remote['id'] ?? null) ? $remote['id'] : null;
        $this->status = self::STATUS_PENDING;
        $this->invoiceCount = 0;
        $this->message = null;
        $this->requestedAt = new \DateTimeImmutable();
        $this->updatedAt = $this->requestedAt;
        $this->completedAt = null;
        $this->applyPeriod($remote);
    }

    /** @param array<string, mixed> $remote */
    public function applyRemote(array $remote): void
    {
        $status = (string) ($remote['status'] ?? '');
        if (!in_array($status, [self::STATUS_PENDING, self::STATUS_COMPLETED, self::STATUS_FAILED], true)) {
            throw new \DomainException('Le statut de factures reçu depuis ApiRecept est invalide.');
        }
        $this->status = $status;
        $this->invoiceCount = max(0, (int) ($remote['invoiceCount'] ?? 0));
        $this->message = $this->nullableText($remote['message'] ?? null);
        $this->applyPeriod($remote);
        $this->updatedAt = new \DateTimeImmutable();
        $this->completedAt = $status === self::STATUS_PENDING ? null : $this->updatedAt;
        if ($status === self::STATUS_COMPLETED) {
            $this->lastSyncedAt = $this->updatedAt;
        }
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function getRemoteRequestId(): ?int { return $this->remoteRequestId; }
    public function getStatus(): string { return $this->status; }
    public function getInvoiceCount(): int { return $this->invoiceCount; }
    public function getMessage(): ?string { return $this->message; }
    public function getRequestedAt(): \DateTimeImmutable { return $this->requestedAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function getCompletedAt(): ?\DateTimeImmutable { return $this->completedAt; }
    public function getLastSyncedAt(): ?\DateTimeImmutable { return $this->lastSyncedAt; }
    public function getPeriodFrom(): ?\DateTimeImmutable { return $this->periodFrom; }
    public function getPeriodTo(): ?\DateTimeImmutable { return $this->periodTo; }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'remoteRequestId' => $this->remoteRequestId,
            'status' => $this->status,
            'invoiceCount' => $this->invoiceCount,
            'message' => $this->message,
            'requestedAt' => $this->requestedAt->format(DATE_ATOM),
            'updatedAt' => $this->updatedAt->format(DATE_ATOM),
            'completedAt' => $this->completedAt?->format(DATE_ATOM),
            'lastSyncedAt' => $this->lastSyncedAt?->format(DATE_ATOM),
            'fromDate' => $this->periodFrom?->format('Y-m-d'),
            'toDate' => $this->periodTo?->format('Y-m-d'),
        ];
    }

    private function nullableText(mixed $value): ?string
    {
        if ($value !== null && !is_scalar($value)) return null;
        $text = mb_substr(trim((string) $value), 0, 1000);

        return $text === '' ? null : $text;
    }

    /** @param array<string, mixed> $remote */
    private function applyPeriod(array $remote): void
    {
        if (!array_key_exists('fromDate', $remote) && !array_key_exists('toDate', $remote)) return;
        $this->periodFrom = $this->periodDate($remote['fromDate'] ?? null);
        $this->periodTo = $this->periodDate($remote['toDate'] ?? null);
        if (($this->periodFrom === null) !== ($this->periodTo === null) || ($this->periodFrom !== null && $this->periodFrom > $this->periodTo)) {
            throw new \DomainException('La période des documents retournée par ApiRecept est invalide.');
        }
    }

    private function periodDate(mixed $value): ?\DateTimeImmutable
    {
        if ($value === null || trim((string) $value) === '') return null;
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $value, new \DateTimeZone('UTC'));
        $errors = \DateTimeImmutable::getLastErrors();
        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw new \DomainException('La période des documents retournée par ApiRecept est invalide.');
        }

        return $date;
    }
}
