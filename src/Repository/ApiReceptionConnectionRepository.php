<?php

namespace App\Repository;

use App\Entity\ApiReceptionConnection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ApiReceptionConnection> */
final class ApiReceptionConnectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiReceptionConnection::class);
    }

    public function findDefault(): ?ApiReceptionConnection
    {
        return $this->findOneBy([], ['id' => 'ASC']);
    }
}
