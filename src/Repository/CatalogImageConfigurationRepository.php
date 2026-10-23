<?php

namespace App\Repository;

use App\Entity\CatalogImageConfiguration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<CatalogImageConfiguration> */
final class CatalogImageConfigurationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, CatalogImageConfiguration::class); }
    public function findDefault(): ?CatalogImageConfiguration { return $this->findOneBy([], ['id' => 'ASC']); }
}
