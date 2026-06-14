<?php

namespace App\ViewModel;

use App\Entity\Article;

class ArticlePricingView
{
    public function __construct(
        private readonly Article $article,
        private readonly ?string $computedReduction
    ) {
    }

    public function getId(): ?string
    {
        return $this->article->getId();
    }

    public function getUniqueId(): ?string
    {
        return $this->article->getUniqueId();
    }

    public function getDesComClear(): ?string
    {
        return $this->article->getDesComClear();
    }

    public function getRealStock(): int
    {
        return $this->article->getRealStock();
    }

    public function getSalePriceVatIncluded(): string
    {
        return $this->article->getSalePriceVatIncluded();
    }

    public function getSalePriceVatExcluded(): string
    {
        return $this->article->getSalePriceVatExcluded();
    }

    public function getFamilyName(): ?string
    {
        return $this->article->getFamilyName();
    }

    public function getXxMarque(): ?string
    {
        return $this->article->getXxMarque();
    }

    public function getXxVehicule(): ?string
    {
        return $this->article->getXxVehicule();
    }

    public function getXxAnnee(): ?string
    {
        return $this->article->getXxAnnee();
    }

    public function getXxPosition(): ?string
    {
        return $this->article->getXxPosition();
    }

    public function getSysModifiedDate(): ?\DateTimeImmutable
    {
        return $this->article->getSysModifiedDate();
    }

    public function getSysCreatedDate(): ?\DateTimeImmutable
    {
        return $this->article->getSysCreatedDate();
    }

    public function getExportStartedAt(): ?\DateTimeImmutable
    {
        return $this->article->getExportStartedAt();
    }

    public function getFtpFinishedAt(): ?\DateTimeImmutable
    {
        return $this->article->getFtpFinishedAt();
    }

    public function getReduction(): ?string
    {
        return $this->computedReduction;
    }

    /**
     * Champs absents de la vraie entity,
     * mais conservés pour ne pas casser les Twig existants.
     */
    public function getXxOem(): ?string
    {
        return null;
    }

    public function getXxCorrespondance(): ?string
    {
        return null;
    }

    public function getXxCodeWeb(): ?string
    {
        return null;
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->article->{$name}(...$arguments);
    }

    public function __get(string $name): mixed
    {
        $getter = 'get' . ucfirst($name);

        if (method_exists($this, $getter)) {
            return $this->{$getter}();
        }

        if (method_exists($this->article, $getter)) {
            return $this->article->{$getter}();
        }

        return null;
    }
}