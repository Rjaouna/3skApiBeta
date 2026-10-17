<?php

namespace App\Entity;

use App\Repository\CategoryReductionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: CategoryReductionRepository::class)]
#[ORM\Table(name: 'category_reduction')]
#[ORM\UniqueConstraint(name: 'uniq_category_reduction_family', columns: ['family_name'])]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['familyName'], message: 'Une réduction existe déjà pour cette catégorie.')]
class CategoryReduction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'family_name', length: 100)]
    private string $familyName = '';

    #[ORM\Column(name: 'discount_rate', type: Types::DECIMAL, precision: 5, scale: 2, options: ['default' => '0.00'])]
    private string $discountRate = '0.00';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'updated_at')]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt ??= $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFamilyName(): string
    {
        return $this->familyName;
    }

    public function setFamilyName(string $familyName): static
    {
        $familyName = preg_replace('/\s+/u', ' ', trim($familyName)) ?? '';
        $this->familyName = mb_strtoupper($familyName, 'UTF-8');

        return $this;
    }

    public function getDiscountRate(): string
    {
        return $this->discountRate;
    }

    public function setDiscountRate(string|float|int $discountRate): static
    {
        $rate = (float) str_replace(',', '.', (string) $discountRate);
        $rate = max(0, min(100, $rate));
        $this->discountRate = number_format($rate, 2, '.', '');

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
