<?php

namespace App\Repository;

use App\Entity\Article;
use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CartItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CartItem::class);
    }

    public function findOneByCartAndArticle(Cart $cart, Article $article): ?CartItem
    {
        return $this->findOneBy(['cart' => $cart, 'article' => $article]);
    }

    public function getPressureMapForArticleCodes(array $codes, ?User $currentUser): array
    {
        $codes = array_values(array_unique(array_filter(array_map('strval', $codes))));
        if ($codes === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('ci')
            ->select('a.id AS code, ci.quantity AS qty, IDENTITY(c.user) AS userId')
            ->join('ci.cart', 'c')
            ->join('ci.article', 'a')
            ->where('c.status = :status')
            ->andWhere('a.id IN (:codes)')
            ->andWhere('c.user IS NOT NULL')
            ->setParameter('status', 'open')
            ->setParameter('codes', $codes)
            ->getQuery()
            ->getArrayResult();

        $currentUserId = $currentUser?->getId();
        $map = [];

        foreach ($rows as $row) {
            $code = (string) ($row['code'] ?? '');
            if ($code === '') {
                continue;
            }

            $qty = (int) ($row['qty'] ?? 0);
            $userId = isset($row['userId']) ? (int) $row['userId'] : 0;

            if (!isset($map[$code])) {
                $map[$code] = [
                    'totalReserved' => 0,
                    'myQty' => 0,
                    'users' => [],
                ];
            }

            $map[$code]['totalReserved'] += $qty;

            if ($currentUserId !== null && $userId === $currentUserId) {
                $map[$code]['myQty'] += $qty;
            }

            if ($userId > 0) {
                $map[$code]['users'][$userId] = true;
            }
        }

        foreach ($map as $code => $data) {
            $map[$code]['usersCount'] = count($data['users']);
            unset($map[$code]['users']);
        }

        return $map;
    }

    public function getDashboardSummary(): array
    {
        $rows = $this->createQueryBuilder('ci')
            ->select('COUNT(ci.id) AS linesCount')
            ->addSelect('COUNT(DISTINCT c.id) AS cartsCount')
            ->addSelect('COUNT(DISTINCT a.uniqueId) AS uniqueArticlesCount')
            ->addSelect('COUNT(DISTINCT u.id) AS customersCount')
            ->addSelect('COALESCE(SUM(ci.quantity), 0) AS itemsQty')
            ->join('ci.cart', 'c')
            ->join('ci.article', 'a')
            ->join('c.user', 'u')
            ->andWhere('c.status = :status')
            ->setParameter('status', 'open')
            ->getQuery()
            ->getArrayResult();

        return $rows[0] ?? [
            'linesCount' => 0,
            'cartsCount' => 0,
            'uniqueArticlesCount' => 0,
            'customersCount' => 0,
            'itemsQty' => 0,
        ];
    }

    public function getDashboardByCustomer(int $limit = 10): array
    {
        $limit = max(1, min($limit, 100));

        return $this->createQueryBuilder('ci')
            ->select('u.id AS userId')
            ->addSelect('u.email AS email')
            ->addSelect('u.companyName AS companyName')
            ->addSelect('COUNT(DISTINCT c.id) AS cartsCount')
            ->addSelect('COUNT(ci.id) AS linesCount')
            ->addSelect('COALESCE(SUM(ci.quantity), 0) AS itemsQty')
            ->join('ci.cart', 'c')
            ->join('c.user', 'u')
            ->andWhere('c.status = :status')
            ->setParameter('status', 'open')
            ->groupBy('u.id')
            ->addGroupBy('u.email')
            ->addGroupBy('u.companyName')
            ->orderBy('itemsQty', 'DESC')
            ->addOrderBy('u.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    public function getDashboardByArticle(int $limit = 10): array
    {
        $limit = max(1, min($limit, 100));

        return $this->createQueryBuilder('ci')
            ->select('a.id AS code')
            ->addSelect('a.desComClear AS designation')
            ->addSelect('a.familyName AS familyName')
            ->addSelect('COUNT(DISTINCT c.id) AS cartsCount')
            ->addSelect('COUNT(DISTINCT u.id) AS customersCount')
            ->addSelect('COALESCE(SUM(ci.quantity), 0) AS itemsQty')
            ->join('ci.cart', 'c')
            ->join('c.user', 'u')
            ->join('ci.article', 'a')
            ->andWhere('c.status = :status')
            ->setParameter('status', 'open')
            ->groupBy('a.uniqueId')
            ->addGroupBy('a.id')
            ->addGroupBy('a.desComClear')
            ->addGroupBy('a.familyName')
            ->orderBy('itemsQty', 'DESC')
            ->addOrderBy('a.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }
}