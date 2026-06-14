<?php

namespace App\Repository;

use App\Entity\CustomerOrder;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CustomerOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomerOrder::class);
    }

    public function findAdminList(?string $status = null): array
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.customer', 'c')->addSelect('c')
            ->leftJoin('o.lines', 'l')->addSelect('l')
            ->orderBy('o.createdAt', 'DESC');

        if ($status !== null && $status !== '') {
            $qb->andWhere('o.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    public function findByCustomer(User $user): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.lines', 'l')->addSelect('l')
            ->andWhere('o.customer = :user')
            ->setParameter('user', $user)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByStatusForCustomer(User $user): array
    {
        $rows = $this->createQueryBuilder('o')
            ->select('o.status AS status, COUNT(o.id) AS total')
            ->andWhere('o.customer = :user')
            ->setParameter('user', $user)
            ->groupBy('o.status')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach (CustomerOrder::getAvailableStatuses() as $status) {
            $counts[$status] = 0;
        }

        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }

        return $counts;
    }

    public function findDashboardOrdersSince(\DateTimeImmutable $since): array
    {
        return $this->createQueryBuilder('o')
            ->select('o.id AS id')
            ->addSelect('o.createdAt AS createdAt')
            ->addSelect('o.subtotalTtc AS subtotalTtc')
            ->addSelect('o.totalTtc AS totalTtc')
            ->andWhere('o.createdAt >= :since')
            ->setParameter('since', $since)
            ->orderBy('o.createdAt', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }
}