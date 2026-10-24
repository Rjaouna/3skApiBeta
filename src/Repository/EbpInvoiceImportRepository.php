<?php

namespace App\Repository;

use App\Entity\EbpInvoiceImport;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<EbpInvoiceImport> */
final class EbpInvoiceImportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, EbpInvoiceImport::class); }

    public function forUser(User $user): ?EbpInvoiceImport { return $this->findOneBy(['user' => $user]); }

    /** @return list<EbpInvoiceImport> */
    public function pending(): array { return $this->findBy(['status' => EbpInvoiceImport::STATUS_PENDING], ['updatedAt' => 'ASC']); }
}
