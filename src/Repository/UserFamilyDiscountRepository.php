<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserFamilyDiscount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserFamilyDiscountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserFamilyDiscount::class);
    }

    public function findAllForAdminList(): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.user', 'u')->addSelect('u')
            ->orderBy('u.email', 'ASC')
            ->addOrderBy('d.familyName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByUserOrdered(User $user): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.user = :user')
            ->setParameter('user', $user)
            ->orderBy('d.familyName', 'ASC')
            ->getQuery()
            ->getResult();
    }
}