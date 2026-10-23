<?php

namespace App\Repository;

use App\Entity\ApiCatalogSyncRun;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ApiCatalogSyncRun> */
final class ApiCatalogSyncRunRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiCatalogSyncRun::class);
    }

    public function findLatest(): ?ApiCatalogSyncRun
    {
        return $this->findOneBy([], ['id' => 'DESC']);
    }
}
