<?php

namespace App\Repository;

use App\Entity\CustomerOrderLine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CustomerOrderLineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomerOrderLine::class);
    }

    public function findDashboardCategoryRowsSince(\DateTimeImmutable $since): array
    {
        return $this->createQueryBuilder('l')
            ->select('o.id AS orderId')
            ->addSelect('o.createdAt AS createdAt')
            ->addSelect('a.familyName AS familyName')
            ->addSelect('l.quantity AS quantity')
            ->addSelect('l.lineTotalBeforeDiscountTtc AS subtotalTtc')
            ->addSelect('l.discountAmountTtc AS discountTtc')
            ->addSelect('l.lineTotalTtc AS totalTtc')
            ->innerJoin('l.orderRef', 'o')
            ->leftJoin('l.article', 'a')
            ->andWhere('o.createdAt >= :since')
            ->setParameter('since', $since)
            ->orderBy('o.createdAt', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }
}