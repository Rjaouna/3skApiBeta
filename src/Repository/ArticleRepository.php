<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    public function findOneByBusinessId(string $id): ?Article
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.id = :id')
            ->setParameter('id', trim($id))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @param list<string> $uniqueIds
     *  @return list<Article>
     */
    public function findByUniqueIds(array $uniqueIds): array
    {
        if ($uniqueIds === []) {
            return [];
        }

        return $this->createQueryBuilder('a')
            ->andWhere('a.uniqueId IN (:uniqueIds)')
            ->setParameter('uniqueIds', $uniqueIds)
            ->getQuery()
            ->getResult();
    }

    public function findAvailableByBusinessId(string $id): ?Article
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.id = :id')
            ->andWhere('a.realStock > 0')
            ->setParameter('id', trim($id))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function searchDistinctFamilyNames(string $term = '', int $limit = 3): array
    {
        $limit = max(1, min($limit, 20));
        $term = trim($term);

        $qb = $this->createQueryBuilder('a')
            ->select('DISTINCT a.familyName AS familyName')
            ->andWhere('a.familyName IS NOT NULL')
            ->andWhere("TRIM(a.familyName) <> ''")
            ->orderBy('a.familyName', 'ASC')
            ->setMaxResults($limit);

        if ($term !== '') {
            $qb
                ->andWhere('UPPER(a.familyName) LIKE :term')
                ->setParameter('term', '%' . mb_strtoupper($term, 'UTF-8') . '%');
        }

        $rows = $qb->getQuery()->getScalarResult();

        $families = [];
        foreach ($rows as $row) {
            $value = trim((string) ($row['familyName'] ?? ''));
            if ($value !== '') {
                $families[] = $value;
            }
        }

        return array_values(array_unique($families));
    }

    public function findDistinctFamilyNames(int $limit = 50): array
    {
        $limit = max(1, min($limit, 500));

        $rows = $this->createQueryBuilder('a')
            ->select('DISTINCT a.familyName AS familyName')
            ->andWhere('a.familyName IS NOT NULL')
            ->andWhere("TRIM(a.familyName) <> ''")
            ->orderBy('a.familyName', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getScalarResult();

        $families = [];
        foreach ($rows as $row) {
            $value = trim((string) ($row['familyName'] ?? ''));
            if ($value !== '') {
                $families[] = $value;
            }
        }

        return array_values(array_unique($families));
    }

    public function findDashboardStockRows(): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.id AS code')
            ->addSelect('a.desComClear AS designation')
            ->addSelect('a.familyName AS familyName')
            ->addSelect('a.realStock AS realStock')
            ->addSelect('a.salePriceVatIncluded AS salePriceVatIncluded')
            ->addSelect('a.salePriceVatExcluded AS salePriceVatExcluded')
            ->addSelect('a.reduction AS reduction')
            ->orderBy('a.id', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }
}
