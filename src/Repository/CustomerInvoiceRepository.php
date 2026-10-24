<?php

namespace App\Repository;

use App\Dto\CustomerDocumentFilter;
use App\Entity\CustomerInvoice;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<CustomerInvoice> */
final class CustomerInvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, CustomerInvoice::class); }

    /** @return list<CustomerInvoice> */
    public function findForCustomer(User $user): array
    {
        return $this->createQueryBuilder('invoice')
            ->andWhere('invoice.customer = :customer')->setParameter('customer', $user)
            ->orderBy('invoice.documentDate', 'DESC')->addOrderBy('invoice.id', 'DESC')
            ->getQuery()->getResult();
    }

    /**
     * The EBP import is explicitly capped upstream. Filtering in PHP lets us search
     * line designations from the portable JSON payload without relying on MySQL-only
     * JSON functions, while keeping the result set bounded for one customer.
     *
     * @return array{items: list<CustomerInvoice>, total: int, page: int, perPage: int, pages: int, from: int, to: int}
     */
    public function paginateForCustomer(User $user, CustomerDocumentFilter $filter, int $page, int $perPage = 10): array
    {
        $perPage = max(10, min(50, $perPage));
        $matches = $this->filteredForCustomer($user, $filter);
        $total = count($matches);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;
        $items = array_slice($matches, $offset, $perPage);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'pages' => $pages,
            'from' => $total === 0 ? 0 : $offset + 1,
            'to' => min($offset + $perPage, $total),
        ];
    }

    /**
     * @return array{counts: array{all: int, invoice: int, credit_note: int, delivery_note: int}, amounts: array{filteredInvoice: float, filteredCreditNote: float, filteredNet: float, currentMonthInvoice: float, previousMonthInvoice: float, currentMonthCreditNote: float}}
     */
    public function statisticsForCustomer(User $user, CustomerDocumentFilter $filter): array
    {
        $documents = $this->filteredForCustomer($user, $filter);
        $counts = ['all' => count($documents), 'invoice' => 0, 'credit_note' => 0, 'delivery_note' => 0];
        foreach ($documents as $document) {
            ++$counts[$document->getDocumentKind()];
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $currentMonthStart = $now->modify('first day of this month')->setTime(0, 0);
        $previousMonthStart = $currentMonthStart->modify('first day of previous month');

        $currentInvoices = $this->totalForPeriod($documents, 'invoice', $currentMonthStart, $now);
        $previousInvoices = $this->totalForPeriod($documents, 'invoice', $previousMonthStart, $currentMonthStart->modify('-1 second'));
        $currentCreditNotes = $this->totalForPeriod($documents, 'credit_note', $currentMonthStart, $now);
        $filteredInvoices = $this->totalForKind($documents, 'invoice');
        $filteredCreditNotes = $this->totalForKind($documents, 'credit_note');

        return [
            'counts' => $counts,
            'amounts' => [
                'filteredInvoice' => $filteredInvoices,
                'filteredCreditNote' => $filteredCreditNotes,
                'filteredNet' => $filteredInvoices + $filteredCreditNotes,
                'currentMonthInvoice' => $currentInvoices,
                'previousMonthInvoice' => $previousInvoices,
                'currentMonthCreditNote' => $currentCreditNotes,
            ],
        ];
    }

    /** @return list<CustomerInvoice> */
    private function filteredForCustomer(User $user, CustomerDocumentFilter $filter): array
    {
        return array_values(array_filter($this->findForCustomer($user), static function (CustomerInvoice $invoice) use ($filter): bool {
            if (!$invoice->matchesSearch($filter->query)) {
                return false;
            }
            if ($filter->kind !== null && $invoice->getDocumentKind() !== $filter->kind) {
                return false;
            }

            $documentDate = $invoice->getDocumentDate();
            if ($filter->from !== null && ($documentDate === null || $documentDate < $filter->from)) {
                return false;
            }
            if ($filter->to !== null && ($documentDate === null || $documentDate > $filter->to)) {
                return false;
            }

            return true;
        }));
    }

    /** @param list<CustomerInvoice> $documents */
    private function totalForPeriod(array $documents, string $kind, \DateTimeImmutable $from, \DateTimeImmutable $to): float
    {
        $total = 0.0;
        foreach ($documents as $document) {
            $date = $document->getDocumentDate();
            if ($document->getDocumentKind() !== $kind || $date === null || $date < $from || $date > $to) {
                continue;
            }
            $total += (float) ($document->getTotalTtc() ?? 0);
        }

        return $total;
    }

    /** @param list<CustomerInvoice> $documents */
    private function totalForKind(array $documents, string $kind): float
    {
        $total = 0.0;
        foreach ($documents as $document) {
            if ($document->getDocumentKind() === $kind) {
                $total += (float) ($document->getTotalTtc() ?? 0);
            }
        }

        return $total;
    }

    /** @param list<string> $sourceIds */
    public function removeMissingForCustomer(User $user, array $sourceIds): int
    {
        $builder = $this->createQueryBuilder('invoice')->delete()
            ->where('invoice.customer = :customer')->setParameter('customer', $user);
        if ($sourceIds !== []) $builder->andWhere('invoice.sourceId NOT IN (:sourceIds)')->setParameter('sourceIds', $sourceIds);

        return $builder->getQuery()->execute();
    }
}
