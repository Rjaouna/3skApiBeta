<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function findByUserPaginated(User $user, int $page = 1, int $limit = 20, ?bool $onlyUnread = null): array
    {
        $page = max(1, $page);
        $limit = max(1, $limit);

        $qb = $this->createQueryBuilder('n')
            ->leftJoin('n.actor', 'a')->addSelect('a')
            ->andWhere('n.recipient = :user')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        if ($onlyUnread === true) {
            $qb->andWhere('n.readAt IS NULL');
        } elseif ($onlyUnread === false) {
            $qb->andWhere('n.readAt IS NOT NULL');
        }

        return $qb->getQuery()->getResult();
    }

    public function countByUser(User $user, ?bool $onlyUnread = null): int
    {
        $qb = $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.recipient = :user')
            ->setParameter('user', $user);

        if ($onlyUnread === true) {
            $qb->andWhere('n.readAt IS NULL');
        } elseif ($onlyUnread === false) {
            $qb->andWhere('n.readAt IS NOT NULL');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countUnreadForUser(User $user): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.recipient = :user')
            ->andWhere('n.readAt IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function deleteReadForUser(User $user): int
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->delete(Notification::class, 'n')
            ->andWhere('n.recipient = :user')
            ->andWhere('n.readAt IS NOT NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}