<?php

namespace App\Service;

use App\Entity\CompanySetting;
use App\Repository\CompanySettingRepository;
use Doctrine\ORM\EntityManagerInterface;

class CompanySettingProvider
{
    public function __construct(
        private readonly CompanySettingRepository $repository,
        private readonly EntityManagerInterface $em
    ) {
    }

    public function get(): CompanySetting
    {
        $setting = $this->repository->getDefaultSetting();

        if ($setting instanceof CompanySetting) {
            return $setting;
        }

        $setting = new CompanySetting();
        $setting->setCode('default');
        $setting->setCompanyName('Hopic');
        $setting->setEmailFrom('commandes@3skpartsauto.com');
        $setting->setEmailFromName('Hopic');
        $setting->setSendOrderEmailToAdmin(true);
        $setting->setSendOrderEmailToCustomer(true);
        $setting->setNotificationEmails(['commandes@3skpartsauto.com']);

        $this->em->persist($setting);
        $this->em->flush();

        return $setting;
    }
}