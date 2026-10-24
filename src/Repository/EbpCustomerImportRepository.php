<?php

namespace App\Repository;

use App\Entity\EbpCustomerImport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EbpCustomerImport>
 */
final class EbpCustomerImportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EbpCustomerImport::class);
    }

    /** @return list<EbpCustomerImport> */
    public function latest(int $limit = 100): array
    {
        return $this->createQueryBuilder('i')
            ->orderBy('i.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** @return list<EbpCustomerImport> */
    public function waitingForRemote(): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.status = :status')
            ->setParameter('status', EbpCustomerImport::STATUS_PENDING)
            ->orderBy('i.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
