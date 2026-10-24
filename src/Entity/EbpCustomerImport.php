<?php

namespace App\Entity;

use App\Repository\EbpCustomerImportRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EbpCustomerImportRepository::class)]
#[ORM\Table(name: 'ebp_customer_import')]
#[ORM\UniqueConstraint(name: 'uniq_ebp_customer_import_unique_id', columns: ['unique_id'])]
class EbpCustomerImport
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CREATED = 'created';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 36)]
    private string $uniqueId;

    #[ORM\Column(nullable: true)]
    private ?int $remoteRequestId = null;

    #[ORM\Column(length: 16)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    /** @var array<string, scalar|null>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $customer = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column]
    private \DateTimeImmutable $requestedAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $uniqueId)
    {
        $this->uniqueId = strtoupper($uniqueId);
        $this->requestedAt = new \DateTimeImmutable();
        $this->updatedAt = $this->requestedAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUniqueId(): string
    {
        return $this->uniqueId;
    }

    public function getRemoteRequestId(): ?int
    {
        return $this->remoteRequestId;
    }

    public function queue(int $remoteRequestId): void
    {
        $this->remoteRequestId = $remoteRequestId;
        $this->status = self::STATUS_PENDING;
        $this->message = null;
        $this->customer = null;
        $this->user = null;
        $this->requestedAt = new \DateTimeImmutable();
        $this->updatedAt = $this->requestedAt;
    }

    public function waiting(): void
    {
        $this->status = self::STATUS_PENDING;
        $this->message = null;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function fail(string $message): void
    {
        $this->status = self::STATUS_FAILED;
        $this->message = mb_substr(trim($message), 0, 1000);
        $this->updatedAt = new \DateTimeImmutable();
    }

    /** @param array<string, scalar|null> $customer */
    public function created(User $user, array $customer): void
    {
        $this->status = self::STATUS_CREATED;
        $this->message = 'Compte client créé sur le site.';
        $this->customer = $customer;
        $this->user = $user;
        $this->updatedAt = new \DateTimeImmutable();
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uniqueId' => $this->uniqueId,
            'remoteRequestId' => $this->remoteRequestId,
            'status' => $this->status,
            'message' => $this->message,
            'customer' => $this->customer,
            'userId' => $this->user?->getId(),
            'userEmail' => $this->user?->getEmail(),
            'requestedAt' => $this->requestedAt->format(DATE_ATOM),
            'updatedAt' => $this->updatedAt->format(DATE_ATOM),
        ];
    }
}
