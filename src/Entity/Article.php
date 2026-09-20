<?php

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArticleRepository::class)]
#[ORM\Table(name: 'article')]
#[ORM\Index(columns: ['FamilyName'], name: 'idx_family')]
#[ORM\Index(columns: ['xx_Marque'], name: 'idx_marque')]
class Article
{
    #[ORM\Id]
    #[ORM\Column(name: 'UniqueId', type: Types::STRING, length: 36)]
    private ?string $uniqueId = null;

    #[ORM\Column(name: 'Id', type: Types::STRING, length: 64)]
    private ?string $id = null;

    #[ORM\Column(name: 'DesComClear', type: Types::STRING, length: 255, nullable: true)]
    private ?string $desComClear = null;

    #[ORM\Column(name: 'RealStock', type: Types::INTEGER, options: ['default' => 0])]
    private int $realStock = 0;

    #[ORM\Column(name: 'SalePriceVatIncluded', type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $salePriceVatIncluded = '0.00';

    #[ORM\Column(name: 'SalePriceVatExcluded', type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $salePriceVatExcluded = '0.00';

    #[ORM\Column(name: 'xx_Position', type: Types::STRING, length: 255, nullable: true)]
    private ?string $xxPosition = null;

    #[ORM\Column(name: 'xx_Annee', type: Types::STRING, length: 255, nullable: true)]
    private ?string $xxAnnee = null;

    #[ORM\Column(name: 'xx_Vehicule', type: Types::STRING, length: 255, nullable: true)]
    private ?string $xxVehicule = null;

    #[ORM\Column(name: 'xx_Marque', type: Types::STRING, length: 100, nullable: true)]
    private ?string $xxMarque = null;

    #[ORM\Column(name: 'sysModifiedDate', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $sysModifiedDate = null;

    #[ORM\Column(name: 'sysCreatedDate', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $sysCreatedDate = null;

    #[ORM\Column(name: 'FamilyName', type: Types::STRING, length: 100, nullable: true)]
    private ?string $familyName = null;

    #[ORM\Column(name: 'ExportStartedAt', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $exportStartedAt = null;

    #[ORM\Column(name: 'FtpFinishedAt', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $ftpFinishedAt = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $reduction = null;

    public function getUniqueId(): ?string
    {
        return $this->uniqueId;
    }

    public function setUniqueId(?string $uniqueId): static
    {
        $this->uniqueId = $uniqueId;
        return $this;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getDesComClear(): ?string
    {
        return $this->desComClear;
    }

    public function setDesComClear(?string $desComClear): static
    {
        $this->desComClear = $desComClear;
        return $this;
    }

    public function getRealStock(): int
    {
        return $this->realStock;
    }

    public function setRealStock(int $realStock): static
    {
        $this->realStock = max(0, $realStock);
        return $this;
    }

    public function getSalePriceVatIncluded(): string
    {
        return $this->salePriceVatIncluded;
    }

    public function setSalePriceVatIncluded(string $salePriceVatIncluded): static
    {
        $this->salePriceVatIncluded = $salePriceVatIncluded !== '' ? $salePriceVatIncluded : '0.00';
        return $this;
    }

    public function getSalePriceVatExcluded(): string
    {
        return $this->salePriceVatExcluded;
    }

    public function setSalePriceVatExcluded(string $salePriceVatExcluded): static
    {
        $this->salePriceVatExcluded = $salePriceVatExcluded !== '' ? $salePriceVatExcluded : '0.00';
        return $this;
    }

    public function getXxPosition(): ?string
    {
        return $this->xxPosition;
    }

    public function setXxPosition(?string $xxPosition): static
    {
        $this->xxPosition = $xxPosition;
        return $this;
    }

    public function getXxAnnee(): ?string
    {
        return $this->xxAnnee;
    }

    public function setXxAnnee(?string $xxAnnee): static
    {
        $this->xxAnnee = $xxAnnee;
        return $this;
    }

    public function getXxVehicule(): ?string
    {
        return $this->xxVehicule;
    }

    public function setXxVehicule(?string $xxVehicule): static
    {
        $this->xxVehicule = $xxVehicule;
        return $this;
    }

    public function getXxMarque(): ?string
    {
        return $this->xxMarque;
    }

    public function setXxMarque(?string $xxMarque): static
    {
        $this->xxMarque = $xxMarque;
        return $this;
    }

    public function getSysModifiedDate(): ?\DateTimeImmutable
    {
        return $this->sysModifiedDate;
    }

    public function setSysModifiedDate(?\DateTimeImmutable $sysModifiedDate): static
    {
        $this->sysModifiedDate = $sysModifiedDate;
        return $this;
    }

    public function getSysCreatedDate(): ?\DateTimeImmutable
    {
        return $this->sysCreatedDate;
    }

    public function setSysCreatedDate(?\DateTimeImmutable $sysCreatedDate): static
    {
        $this->sysCreatedDate = $sysCreatedDate;
        return $this;
    }

    public function getFamilyName(): ?string
    {
        return $this->familyName;
    }

    public function setFamilyName(?string $familyName): static
    {
        $this->familyName = $familyName;
        return $this;
    }

    public function getExportStartedAt(): ?\DateTimeImmutable
    {
        return $this->exportStartedAt;
    }

    public function setExportStartedAt(?\DateTimeImmutable $exportStartedAt): static
    {
        $this->exportStartedAt = $exportStartedAt;
        return $this;
    }

    public function getFtpFinishedAt(): ?\DateTimeImmutable
    {
        return $this->ftpFinishedAt;
    }

    public function setFtpFinishedAt(?\DateTimeImmutable $ftpFinishedAt): static
    {
        $this->ftpFinishedAt = $ftpFinishedAt;
        return $this;
    }

    public function getReduction(): ?string
    {
        return $this->reduction;
    }

    public function setReduction(?string $reduction): static
    {
        $this->reduction = $reduction;

        return $this;
    }
}
