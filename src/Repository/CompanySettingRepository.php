<?php

namespace App\Repository;

use App\Entity\CompanySetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CompanySettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CompanySetting::class);
    }

    public function getDefaultSetting(): ?CompanySetting
    {
        return $this->findOneBy(['code' => 'default']);
    }
}