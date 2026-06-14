<?php

namespace App\Entity;

use App\Repository\CustomerOrderLineRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CustomerOrderLineRepository::class)]
#[ORM\Table(name: 'customer_order_line')]
class CustomerOrderLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CustomerOrder::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?CustomerOrder $orderRef = null;

    #[ORM\ManyToOne(targetEntity: Article::class)]
    #[ORM\JoinColumn(
        name: 'article_unique_id',
        referencedColumnName: 'UniqueId',
        nullable: true,
        onDelete: 'SET NULL'
    )]
    private ?Article $article = null;

    #[ORM\Column(length: 255)]
    private string $articleCode = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $designation = null;

    #[ORM\Column]
    private int $quantity = 0;

    /**
     * Prix unitaire TTC final (après remise)
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $unitPriceTtc = '0.00';

    /**
     * Total final de ligne (après remise)
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $lineTotalTtc = '0.00';

    /**
     * Prix unitaire TTC avant remise
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $unitPriceBeforeDiscountTtc = '0.00';

    /**
     * Taux de remise de la ligne
     * ex: 35.00
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $discountRate = null;

    /**
     * Montant total de remise de la ligne
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $discountAmountTtc = '0.00';

    /**
     * Total TTC avant remise
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $lineTotalBeforeDiscountTtc = '0.00';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderRef(): ?CustomerOrder
    {
        return $this->orderRef;
    }

    public function setOrderRef(?CustomerOrder $orderRef): static
    {
        $this->orderRef = $orderRef;
        return $this;
    }

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setArticle(?Article $article): static
    {
        $this->article = $article;
        return $this;
    }

    public function getArticleCode(): string
    {
        return $this->articleCode;
    }

    public function setArticleCode(string $articleCode): static
    {
        $this->articleCode = $articleCode;
        return $this;
    }

    public function getDesignation(): ?string
    {
        return $this->designation;
    }

    public function setDesignation(?string $designation): static
    {
        $this->designation = $designation;
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = max(0, $quantity);
        return $this;
    }

    /**
     * Prix final après remise
     */
    public function getUnitPriceTtc(): string
    {
        return $this->unitPriceTtc;
    }

    /**
     * Prix final après remise
     */
    public function setUnitPriceTtc(string $unitPriceTtc): static
    {
        $this->unitPriceTtc = $unitPriceTtc;
        return $this;
    }

    /**
     * Total final après remise
     */
    public function getLineTotalTtc(): string
    {
        return $this->lineTotalTtc;
    }

    /**
     * Total final après remise
     */
    public function setLineTotalTtc(string $lineTotalTtc): static
    {
        $this->lineTotalTtc = $lineTotalTtc;
        return $this;
    }

    public function getUnitPriceBeforeDiscountTtc(): string
    {
        return $this->unitPriceBeforeDiscountTtc;
    }

    public function setUnitPriceBeforeDiscountTtc(string $unitPriceBeforeDiscountTtc): static
    {
        $this->unitPriceBeforeDiscountTtc = $unitPriceBeforeDiscountTtc;
        return $this;
    }

    public function getDiscountRate(): ?string
    {
        return $this->discountRate;
    }

    public function setDiscountRate(?string $discountRate): static
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

    public function getLineTotalBeforeDiscountTtc(): string
    {
        return $this->lineTotalBeforeDiscountTtc;
    }

    public function setLineTotalBeforeDiscountTtc(string $lineTotalBeforeDiscountTtc): static
    {
        $this->lineTotalBeforeDiscountTtc = $lineTotalBeforeDiscountTtc;
        return $this;
    }
}