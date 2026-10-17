<?php

namespace App\Repository;

use App\Entity\CategoryReduction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CategoryReductionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategoryReduction::class);
    }

    public function findOneByFamilyName(string $familyName): ?CategoryReduction
    {
        return $this->findOneBy(['familyName' => $this->normalizeFamilyName($familyName)]);
    }

    /**
     * @return array<string, string>
     */
    public function findDiscountRateMap(): array
    {
        $rules = $this->createQueryBuilder('reduction')
            ->orderBy('reduction.familyName', 'ASC')
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($rules as $rule) {
            if (!$rule instanceof CategoryReduction) {
                continue;
            }

            $map[$rule->getFamilyName()] = $rule->getDiscountRate();
        }

        return $map;
    }

    private function normalizeFamilyName(string $familyName): string
    {
        $familyName = preg_replace('/\s+/u', ' ', trim($familyName)) ?? '';

        return mb_strtoupper($familyName, 'UTF-8');
    }
}
