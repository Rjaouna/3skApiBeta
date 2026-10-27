<?php

namespace App\Entity;

use App\Repository\ApiReceptionConnectionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApiReceptionConnectionRepository::class)]
#[ORM\Table(name: 'api_reception_connection')]
class ApiReceptionConnection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'base_url', length: 500, nullable: true)]
    private ?string $baseUrl = null;

    #[ORM\Column(name: 'api_key_ciphertext', type: 'text', nullable: true)]
    private ?string $apiKeyCiphertext = null;

    #[ORM\Column(name: 'is_enabled')]
    private bool $enabled = false;

    #[ORM\Column(name: 'sync_interval_minutes')]
    private int $syncIntervalMinutes = 5;

    #[ORM\Column(name: 'last_tested_at', nullable: true)]
    private ?\DateTimeImmutable $lastTestedAt = null;

    #[ORM\Column(name: 'last_synced_at', nullable: true)]
    private ?\DateTimeImmutable $lastSyncedAt = null;

    /** Last ApiRecept catalogue revision committed by this site. */
    #[ORM\Column(name: 'catalog_revision', options: ['default' => 0])]
    private int $catalogRevision = 0;

    #[ORM\Column(name: 'last_error', type: 'text', nullable: true)]
    private ?string $lastError = null;

    #[ORM\Column(name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    public function setBaseUrl(?string $baseUrl): self
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    public function getApiKeyCiphertext(): ?string
    {
        return $this->apiKeyCiphertext;
    }

    public function setApiKeyCiphertext(?string $apiKeyCiphertext): self
    {
        $this->apiKeyCiphertext = $apiKeyCiphertext;

        return $this;
    }

    public function clearApiKey(): self
    {
        $this->apiKeyCiphertext = null;

        return $this;
    }

    public function hasApiKey(): bool
    {
        return $this->apiKeyCiphertext !== null && $this->apiKeyCiphertext !== '';
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getSyncIntervalMinutes(): int
    {
        return $this->syncIntervalMinutes;
    }

    public function setSyncIntervalMinutes(int $syncIntervalMinutes): self
    {
        $this->syncIntervalMinutes = $syncIntervalMinutes;

        return $this;
    }

    public function getLastTestedAt(): ?\DateTimeImmutable
    {
        return $this->lastTestedAt;
    }

    public function markTested(): self
    {
        $this->lastTestedAt = new \DateTimeImmutable();
        $this->lastError = null;
        $this->touch();

        return $this;
    }

    public function getLastSyncedAt(): ?\DateTimeImmutable
    {
        return $this->lastSyncedAt;
    }

    public function markSynced(): self
    {
        $this->lastSyncedAt = new \DateTimeImmutable();
        $this->lastError = null;
        $this->touch();

        return $this;
    }

    public function getCatalogRevision(): int
    {
        return $this->catalogRevision;
    }

    public function setCatalogRevision(int $catalogRevision): self
    {
        $this->catalogRevision = max(0, $catalogRevision);

        return $this;
    }

    public function resetCatalogRevision(): self
    {
        $this->catalogRevision = 0;
        $this->lastSyncedAt = null;

        return $this;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function markError(string $message): self
    {
        $this->lastError = $message;
        $this->touch();

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function touch(): self
    {
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }
}
