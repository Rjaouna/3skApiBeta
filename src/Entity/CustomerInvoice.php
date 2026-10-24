<?php

namespace App\Entity;

use App\Repository\CustomerInvoiceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CustomerInvoiceRepository::class)]
#[ORM\Table(name: 'customer_invoice')]
#[ORM\UniqueConstraint(name: 'uniq_customer_invoice_source', columns: ['customer_id', 'source_id'])]
#[ORM\Index(name: 'idx_customer_invoice_customer_date', columns: ['customer_id', 'document_date'])]
class CustomerInvoice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'customer_id', nullable: false, onDelete: 'CASCADE')]
    private User $customer;

    #[ORM\Column(name: 'source_id', length: 191)]
    private string $sourceId;

    #[ORM\Column(name: 'document_number', length: 191, nullable: true)]
    private ?string $documentNumber = null;

    #[ORM\Column(name: 'document_type', length: 100, nullable: true)]
    private ?string $documentType = null;

    #[ORM\Column(name: 'document_date', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $documentDate = null;

    #[ORM\Column(name: 'document_state', length: 100, nullable: true)]
    private ?string $documentState = null;

    #[ORM\Column(name: 'total_ttc', type: Types::DECIMAL, precision: 14, scale: 2, nullable: true)]
    private ?string $totalTtc = null;

    #[ORM\Column(name: 'total_ht', type: Types::DECIMAL, precision: 14, scale: 2, nullable: true)]
    private ?string $totalHt = null;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $payload = [];

    #[ORM\Column(name: 'synced_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $syncedAt;

    /** @param array<string, mixed> $data */
    public function __construct(User $customer, array $data)
    {
        $this->customer = $customer;
        $this->sourceId = (string) $data['sourceId'];
        $this->syncedAt = new \DateTimeImmutable();
        $this->apply($data);
    }

    /** @param array<string, mixed> $data */
    public function apply(array $data): void
    {
        $this->documentNumber = $this->nullableText($data['documentNumber'] ?? null, 191);
        $this->documentType = $this->nullableText($data['documentType'] ?? null, 100);
        $this->documentState = $this->nullableText($data['state'] ?? null, 100);
        $this->documentDate = $this->date($data['documentDate'] ?? null);
        $this->totalTtc = $this->amount($data['totalTtc'] ?? null);
        $this->totalHt = $this->amount($data['totalHt'] ?? null);
        $this->payload = $data;
        $this->syncedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getCustomer(): User { return $this->customer; }
    public function getSourceId(): string { return $this->sourceId; }
    public function getDocumentNumber(): ?string { return $this->documentNumber; }
    public function getDocumentType(): ?string { return $this->documentType; }
    public function getDocumentDate(): ?\DateTimeImmutable { return $this->documentDate; }
    public function getDocumentState(): ?string { return $this->documentState; }
    public function getTotalTtc(): ?string { return $this->totalTtc; }
    public function getTotalHt(): ?string { return $this->totalHt; }
    public function getSyncedAt(): \DateTimeImmutable { return $this->syncedAt; }

    public function getDueDate(): ?\DateTimeImmutable
    {
        return $this->date($this->payload['dueDate'] ?? null);
    }

    public function getDocumentKind(): string
    {
        $kind = $this->payload['documentKind'] ?? null;
        if ($kind === 'credit_note') {
            return 'credit_note';
        }
        if ($kind === 'delivery_note') {
            return 'delivery_note';
        }
        if ($kind === 'invoice') {
            return 'invoice';
        }

        $type = self::normalizeSearch((string) $this->documentType);
        if ($type === '3') {
            return 'credit_note';
        }
        if ($type === '6') {
            return 'delivery_note';
        }
        if (str_contains($type, 'avoir') || str_contains($type, 'credit memo') || str_contains($type, 'credit note')) {
            return 'credit_note';
        }
        if (str_contains($type, 'bon de livraison') || str_contains($type, 'delivery order') || str_contains($type, 'delivery note')) {
            return 'delivery_note';
        }
        if (preg_match('/^AV[\s_-]*\d/i', (string) $this->documentNumber) === 1) {
            return 'credit_note';
        }
        if (preg_match('/^BL[\s_-]*\d/i', (string) $this->documentNumber) === 1) {
            return 'delivery_note';
        }

        return 'invoice';
    }

    public function isCreditMemo(): bool { return $this->getDocumentKind() === 'credit_note'; }
    public function isDeliveryNote(): bool { return $this->getDocumentKind() === 'delivery_note'; }
    public function getDocumentLabel(): string
    {
        return match ($this->getDocumentKind()) {
            'credit_note' => 'Avoir',
            'delivery_note' => 'Bon de livraison',
            default => 'Facture',
        };
    }

    /** A document issued during the last five complete days is highlighted in the customer list. */
    public function isNewDocument(): bool
    {
        if ($this->documentDate === null) {
            return false;
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $cutoff = $now->sub(new \DateInterval('P5D'));

        return $this->documentDate >= $cutoff && $this->documentDate <= $now;
    }

    public function getTotalVat(): ?string
    {
        if ($this->totalTtc === null || $this->totalHt === null) {
            return null;
        }

        return number_format((float) $this->totalTtc - (float) $this->totalHt, 2, '.', '');
    }

    /** @return array{companyName: ?string, customerCode: ?string, email: ?string, phone: ?string, address1: ?string, address2: ?string, city: ?string, country: ?string} */
    public function getBilling(): array
    {
        $payload = $this->payload['billing'] ?? [];
        $payload = is_array($payload) && !array_is_list($payload) ? $payload : [];
        $fallback = [
            'companyName' => $this->customer->getCompanyName(),
            'customerCode' => $this->customer->getCustomerCode(),
            'email' => $this->customer->getEmail(),
            'phone' => null,
            'address1' => $this->customer->getAddress1(),
            'address2' => $this->customer->getAddress2(),
            'city' => $this->customer->getCity(),
            'country' => null,
        ];
        $billing = [];
        foreach ($fallback as $field => $value) {
            $candidate = $payload[$field] ?? $value;
            $billing[$field] = is_scalar($candidate) && trim((string) $candidate) !== '' ? mb_substr(trim((string) $candidate), 0, 1000) : null;
        }

        return $billing;
    }

    /** @return list<array<string, mixed>> */
    public function getLines(): array
    {
        $lines = $this->payload['lines'] ?? [];

        return is_array($lines) && array_is_list($lines) ? $lines : [];
    }

    public function matchesSearch(string $query): bool
    {
        $needle = self::normalizeSearch($query);
        if ($needle === '') {
            return true;
        }

        $values = [$this->sourceId, $this->documentNumber, $this->documentType, $this->documentState, $this->getDocumentLabel()];
        foreach ($this->getLines() as $line) {
            foreach (['itemId', 'designation', 'sourceId'] as $field) {
                $value = $line[$field] ?? null;
                if (is_scalar($value)) {
                    $values[] = (string) $value;
                }
            }
        }

        foreach ($values as $value) {
            if ($value !== null && str_contains(self::normalizeSearch((string) $value), $needle)) {
                return true;
            }
        }

        return false;
    }

    private function nullableText(mixed $value, int $limit): ?string
    {
        if ($value !== null && !is_scalar($value)) {
            return null;
        }
        $text = mb_substr(trim((string) $value), 0, $limit);

        return $text === '' ? null : $text;
    }

    private function amount(mixed $value): ?string
    {
        if (!is_scalar($value) || !is_numeric((string) $value)) {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function date(mixed $value): ?\DateTimeImmutable
    {
        if (!is_scalar($value) || trim((string) $value) === '') {
            return null;
        }
        try {
            return new \DateTimeImmutable((string) $value);
        } catch (\Exception) {
            return null;
        }
    }

    private static function normalizeSearch(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        return $transliterated === false ? $value : mb_strtolower($transliterated);
    }
}
