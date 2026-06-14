<?php

namespace App\Entity;

use App\Repository\UserFamilyDiscountRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserFamilyDiscountRepository::class)]
#[ORM\Table(name: 'user_family_discount')]
#[ORM\UniqueConstraint(name: 'uniq_user_family_discount', columns: ['user_id', 'family_name'])]
#[ORM\Index(name: 'idx_user_family_discount_user', columns: ['user_id'])]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(
    fields: ['user', 'familyName'],
    message: 'Une remise existe déjà pour cet utilisateur et cette famille.'
)]
class UserFamilyDiscount
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Utilisateur obligatoire.')]
    private ?User $user = null;

    #[ORM\Column(name: 'family_name', length: 100)]
    #[Assert\NotBlank(message: 'La famille est obligatoire.')]
    #[Assert\Length(max: 100)]
    private string $familyName = '';

    #[ORM\Column(name: 'discount_rate', type: Types::DECIMAL, precision: 5, scale: 2, options: ['default' => '0.00'])]
    #[Assert\NotNull]
    #[Assert\Range(
        min: 0,
        max: 100,
        notInRangeMessage: 'La remise doit être comprise entre {{ min }} et {{ max }}.'
    )]
    private string $discountRate = '0.00';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'updated_at')]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();

        if ($this->createdAt === null) {
            $this->createdAt = $now;
        }

        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
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

    public function getDiscountRateAsFloat(): float
    {
        return (float) $this->discountRate;
    }

    public function setDiscountRate(string|float|int $discountRate): static
    {
        $rate = (float) str_replace(',', '.', (string) $discountRate);

        if ($rate < 0) {
            $rate = 0;
        }

        if ($rate > 100) {
            $rate = 100;
        }

        $this->discountRate = number_format($rate, 2, '.', '');

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}