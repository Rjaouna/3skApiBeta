<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // ADMIN
        $admin = new User();
        $admin->setEmail('admin@3skpartsauto.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setIsActive(true);
        $admin->setCompanyName('3SK Parts Auto');
        $admin->setCustomerCode('ADMIN001');
        $admin->setPassword(
            $this->passwordHasher->hashPassword($admin, 'admin@3skpartsauto.com')
        );

        $manager->persist($admin);

        // CLIENT NORMAL
        $client = new User();
        $client->setEmail('client@3skpartsauto.com');
        $client->setRoles(['ROLE_USER']);
        $client->setIsActive(true);
        $client->setCompanyName('Auto parts client');
        $client->setCustomerCode('CLIENT001');
        $client->setPassword(
            $this->passwordHasher->hashPassword($client, 'client@3skpartsauto.com')
        );

        $manager->persist($client);

        $manager->flush();
    }
}