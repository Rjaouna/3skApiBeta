<?php

namespace App\Entity;

use App\Repository\CatalogImageConfigurationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CatalogImageConfigurationRepository::class)]
#[ORM\Table(name: 'catalog_image_configuration')]
class CatalogImageConfiguration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'base_url', length: 500, nullable: true)]
    private ?string $baseUrl = null;

    #[ORM\Column(name: 'last_tested_at', nullable: true)]
    private ?\DateTimeImmutable $lastTestedAt = null;

    #[ORM\Column(name: 'last_error', type: 'text', nullable: true)]
    private ?string $lastError = null;

    #[ORM\Column(name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getBaseUrl(): ?string { return $this->baseUrl; }
    public function setBaseUrl(?string $baseUrl): self { $this->baseUrl = $baseUrl; return $this; }
    public function getLastTestedAt(): ?\DateTimeImmutable { return $this->lastTestedAt; }
    public function getLastError(): ?string { return $this->lastError; }
    public function markTested(): self { $this->lastTestedAt = new \DateTimeImmutable(); $this->lastError = null; return $this->touch(); }
    public function markError(string $error): self { $this->lastError = $error; return $this->touch(); }
    public function touch(): self { $this->updatedAt = new \DateTimeImmutable(); return $this; }
}
