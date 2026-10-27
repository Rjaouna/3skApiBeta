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

    public function getClientOrderStats(\DateTimeImmutable $monthStart, \DateTimeImmutable $nextMonthStart): array
    {
        $rows = $this->createQueryBuilder('o')
            ->select('IDENTITY(o.customer) AS customerId')
            ->addSelect('MAX(o.createdAt) AS lastOrderAt')
            ->addSelect('SUM(CASE WHEN o.createdAt >= :monthStart AND o.createdAt < :nextMonthStart THEN 1 ELSE 0 END) AS monthlyOrders')
            ->groupBy('o.customer')
            ->setParameter('monthStart', $monthStart)
            ->setParameter('nextMonthStart', $nextMonthStart)
            ->getQuery()
            ->getArrayResult();

        $stats = [];
        foreach ($rows as $row) {
            $stats[(int) $row['customerId']] = [
                'lastOrderAt' => $row['lastOrderAt'] instanceof \DateTimeInterface
                    ? $row['lastOrderAt']->format('Y-m-d H:i:s')
                    : (string) $row['lastOrderAt'],
                'monthlyOrders' => (int) $row['monthlyOrders'],
            ];
        }

        return $stats;
    }

    public function findAdminList(
        ?string $status = null,
        ?string $query = null,
        ?\DateTimeInterface $dateFrom = null,
        ?\DateTimeInterface $dateTo = null,
        ?string $city = null
    ): array
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.customer', 'c')->addSelect('c')
            ->leftJoin('o.lines', 'l')->addSelect('l')
            ->orderBy('o.createdAt', 'DESC');

        if ($status !== null && $status !== '') {
            $qb->andWhere('o.status = :status')
               ->setParameter('status', $status);
        }

        $query = trim((string) $query);
        if ($query !== '') {
            $qb->andWhere('LOWER(o.orderNumber) LIKE :query OR LOWER(o.customerEmail) LIKE :query OR LOWER(o.companyName) LIKE :query OR LOWER(o.customerCode) LIKE :query')
               ->setParameter('query', '%' . mb_strtolower($query) . '%');
        }

        if ($dateFrom !== null) {
            $qb->andWhere('o.createdAt >= :dateFrom')
               ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo !== null) {
            $qb->andWhere('o.createdAt <= :dateTo')
               ->setParameter('dateTo', $dateTo);
        }

        $city = trim((string) $city);
        if ($city !== '') {
            $qb->andWhere('LOWER(o.city) LIKE :city')
               ->setParameter('city', '%' . mb_strtolower($city) . '%');
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

    /**
     * Orders that have never been accepted by ApiRecept. The commercial
     * `new` state is deliberately used here so the admin's bulk action never
     * replays an order that has already been exported.
     *
     * @return list<CustomerOrder>
     */
    public function findNewForEbpExport(): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.customer', 'c')->addSelect('c')
            ->leftJoin('o.lines', 'l')->addSelect('l')
            ->leftJoin('l.article', 'a')->addSelect('a')
            ->andWhere('o.status = :status')
            ->setParameter('status', CustomerOrder::STATUS_NEW)
            ->orderBy('o.createdAt', 'ASC')
            ->getQuery()->getResult();
    }

    /** @return list<CustomerOrder> */
    public function awaitingEbpDispatch(int $limit = 50): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.customer', 'c')->addSelect('c')
            ->leftJoin('o.lines', 'l')->addSelect('l')
            ->andWhere('o.ebpTransferStatus IN (:statuses)')
            ->setParameter('statuses', [CustomerOrder::EBP_STATUS_PENDING_API, CustomerOrder::EBP_STATUS_FAILED])
            ->orderBy('o.createdAt', 'ASC')
            ->setMaxResults(max(1, min(200, $limit)))
            ->getQuery()->getResult();
    }

    /** @return list<CustomerOrder> */
    public function awaitingEbpStatus(int $limit = 100): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.customer', 'c')->addSelect('c')
            ->leftJoin('o.lines', 'l')->addSelect('l')
            ->andWhere('o.ebpTransferStatus IN (:statuses)')
            ->andWhere('o.apiReceptionOrderId IS NOT NULL')
            ->setParameter('statuses', [CustomerOrder::EBP_STATUS_EXPORTED, CustomerOrder::EBP_STATUS_FAILED])
            ->orderBy('o.ebpTransferUpdatedAt', 'ASC')
            ->setMaxResults(max(1, min(200, $limit)))
            ->getQuery()->getResult();
    }
}
