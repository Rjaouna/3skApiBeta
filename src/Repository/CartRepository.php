<?php

namespace App\Repository;

use App\Entity\Cart;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CartRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cart::class);
    }

    public function findOpenByUser(User $user): ?Cart
    {
        $cart = $this->findOneBy(
            [
                'user' => $user,
                'status' => 'open',
            ],
            [
                'id' => 'DESC',
            ]
        );

        if (!$cart) {
            return null;
        }

        return $this->createQueryBuilder('c')
            ->leftJoin('c.items', 'i')->addSelect('i')
            ->leftJoin('i.article', 'a')->addSelect('a')
            ->andWhere('c.id = :id')
            ->setParameter('id', $cart->getId())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getOrCreateOpenCart(User $user): Cart
    {
        $cart = $this->findOpenByUser($user);
        if ($cart) {
            return $cart;
        }

        $cart = new Cart();
        $cart->setUser($user);
        $cart->setStatus('open');

        $em = $this->getEntityManager();
        $em->persist($cart);
        $em->flush();

        return $cart;
    }

    public function countOpenCartsWithUser(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.status = :status')
            ->andWhere('c.user IS NOT NULL')
            ->setParameter('status', 'open')
            ->getQuery()
            ->getSingleScalarResult();
    }
}