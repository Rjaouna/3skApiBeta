<?php

namespace App\Service;

use App\Entity\ApiReceptionConnection;
use App\Repository\ApiReceptionConnectionRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ApiReceptionConnectionProvider
{
    public function __construct(
        private readonly ApiReceptionConnectionRepository $connections,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function get(): ApiReceptionConnection
    {
        $connection = $this->connections->findDefault();
        if ($connection instanceof ApiReceptionConnection) {
            return $connection;
        }

        $connection = new ApiReceptionConnection();
        $this->entityManager->persist($connection);
        $this->entityManager->flush();

        return $connection;
    }
}
