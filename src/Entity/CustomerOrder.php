<?php

namespace App\Entity;

use App\Repository\CustomerOrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CustomerOrderRepository::class)]
#[ORM\Table(name: 'customer_order')]
#[ORM\Index(name: 'idx_customer_order_status', columns: ['status'])]
#[ORM\Index(name: 'idx_customer_order_created_at', columns: ['created_at'])]
class CustomerOrder
{
    public const STATUS_NEW = 'new';
    public const STATUS_PREPARING = 'preparing';
    public const STATUS_SHIPPING = 'shipping';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $orderNumber = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $customer = null;

    #[ORM\Column(length: 30)]
    private string $status = self::STATUS_NEW;

    #[ORM\Column(length: 180)]
    private string $customerEmail = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $companyName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customerCode = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $address1 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $address2 = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $subtotalTtc = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private string $discountRate = '35.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $discountAmountTtc = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $totalTtc = '0.00';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'updated_at')]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'orderRef', targetEntity: CustomerOrderLine::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $lines;

    public function __construct()
    {
        $this->lines = new ArrayCollection();
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->discountRate = '35.00';
        $this->status = self::STATUS_NEW;
    }

    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_NEW,
            self::STATUS_PREPARING,
            self::STATUS_SHIPPING,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
        ];
    }

    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_NEW => 'Nouveau',
            self::STATUS_PREPARING => 'En préparation',
            self::STATUS_SHIPPING => 'En cours de livraison',
            self::STATUS_COMPLETED => 'Terminée',
            self::STATUS_CANCELLED => 'Annulée',
        ];
    }

    public static function getNextStatus(?string $status): ?string
    {
        return match ($status) {
            self::STATUS_NEW => self::STATUS_PREPARING,
            self::STATUS_PREPARING => self::STATUS_SHIPPING,
            self::STATUS_SHIPPING => self::STATUS_COMPLETED,
            default => null,
        };
    }

    public function getStatusLabel(): string
    {
        return self::getStatusLabels()[$this->status] ?? $this->status;
    }

    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(string $orderNumber): static
    {
        $this->orderNumber = $orderNumber;
        return $this;
    }

    public function getCustomer(): ?User
    {
        return $this->customer;
    }

    public function setCustomer(?User $customer): static
    {
        $this->customer = $customer;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        if (!in_array($status, self::getAvailableStatuses(), true)) {
            throw new \InvalidArgumentException('Statut de commande invalide.');
        }

        $this->status = $status;
        $this->touch();

        return $this;
    }

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(string $customerEmail): static
    {
        $this->customerEmail = $customerEmail;
        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): static
    {
        $this->companyName = $companyName;
        return $this;
    }

    public function getCustomerCode(): ?string
    {
        return $this->customerCode;
    }

    public function setCustomerCode(?string $customerCode): static
    {
        $this->customerCode = $customerCode;
        return $this;
    }

    public function getAddress1(): ?string
    {
        return $this->address1;
    }

    public function setAddress1(?string $address1): static
    {
        $this->address1 = $address1;
        return $this;
    }

    public function getAddress2(): ?string
    {
        return $this->address2;
    }

    public function setAddress2(?string $address2): static
    {
        $this->address2 = $address2;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getSubtotalTtc(): string
    {
        return $this->subtotalTtc;
    }

    public function setSubtotalTtc(string $subtotalTtc): static
    {
        $this->subtotalTtc = $subtotalTtc;
        return $this;
    }

    public function getDiscountRate(): string
    {
        return $this->discountRate;
    }

    public function setDiscountRate(string $discountRate): static
    {
        $this->discountRate = $discountRate;
        return $this;
    }

    public function getDiscountAmountTtc(): string
    {
        return $this->discountAmountTtc;
    }

    public function setDiscountAmountTtc(string $discountAmountTtc): static
    {
        $this->discountAmountTtc = $discountAmountTtc;
        return $this;
    }

    public function getTotalTtc(): string
    {
        return $this->totalTtc;
    }

    public function setTotalTtc(string $totalTtc): static
    {
        $this->totalTtc = $totalTtc;
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

    /**
     * @return Collection<int, CustomerOrderLine>
     */
    public function getLines(): Collection
    {
        return $this->lines;
    }

    public function addLine(CustomerOrderLine $line): static
    {
        if (!$this->lines->contains($line)) {
            $this->lines->add($line);
            $line->setOrderRef($this);
        }

        return $this;
    }

    public function removeLine(CustomerOrderLine $line): static
    {
        if ($this->lines->removeElement($line)) {
            if ($line->getOrderRef() === $this) {
                $line->setOrderRef(null);
            }
        }

        return $this;
    }
}