<?php

namespace App\Service;

use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderLine;
use App\Exception\ApiReceptionException;
use App\Repository\CustomerOrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Lock\LockFactory;

/**
 * Transfers the immutable commercial order snapshot to ApiRecept then follows
 * its processing by ApiLocal. Once ApiRecept accepts the snapshot, the visible
 * commercial state moves from "Nouveau" to "Exportée"; the technical state
 * still records the following ApiLocal/EBP processing steps independently.
 */
final class EbpOrderExportService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CustomerOrderRepository $orders,
        private readonly ApiReceptionConnectionProvider $connections,
        private readonly ApiReceptionClient $api,
        private readonly LockFactory $locks,
    ) {
    }

    public function queue(CustomerOrder $order): void
    {
        $order->queueForEbp();
    }

    /**
     * Sends one order. It is safe to call repeatedly: ApiRecept identifies it
     * by the SiteWeb order id and replaces only a failed/payload-changed entry.
     */
    public function dispatch(CustomerOrder $order): void
    {
        if (!$order->shouldBeDispatchedToEbp()) {
            return;
        }

        try {
            $remote = $this->api->submitOrderExport($this->connections->get(), $this->payload($order));
            $item = $remote['item'] ?? null;
            if (($remote['ok'] ?? false) !== true || !is_array($item) || (string) ($item['sourceOrderId'] ?? '') !== (string) $order->getId()) {
                throw new ApiReceptionException('ApiRecept n’a pas confirmé la réception de la commande.');
            }
            $order->applyEbpTransfer($item);
            if ($order->getEbpTransferStatus() === CustomerOrder::EBP_STATUS_FAILED) {
                throw new ApiReceptionException(
                    $order->getEbpTransferMessage() ?? 'ApiRecept a refusé l’export de cette commande.'
                );
            }
            $this->markCommercialOrderAsExportedIfAccepted($order);
            $this->entityManager->flush();
        } catch (\Throwable $exception) {
            $order->recordEbpTransferFailure($this->safeMessage($exception));
            $this->entityManager->flush();
            if ($exception instanceof ApiReceptionException) {
                throw $exception;
            }
            throw new ApiReceptionException('La transmission de la commande vers ApiRecept sera relancée automatiquement.', 0, $exception);
        }
    }

    /**
     * Manually sends every order that is still commercially "Nouveau".
     * Each delivery is independent: one unreachable order does not prevent
     * the following ones from being delivered. Failed deliveries stay
     * "Nouveau" and are eligible for the next retry.
     *
     * @return array{total: int, exported: int, failed: int}
     */
    public function exportAllNewOrders(): array
    {
        $lock = $this->locks->createLock('ebp-order-export-sync', 300.0);
        if (!$lock->acquire()) {
            throw new ApiReceptionException('Un export de commandes EBP est déjà en cours.');
        }

        try {
            $result = ['total' => 0, 'exported' => 0, 'failed' => 0];

            foreach ($this->orders->findNewForEbpExport() as $order) {
                ++$result['total'];
                $this->queue($order);

                try {
                    $this->dispatch($order);
                    if ($order->getStatus() === CustomerOrder::STATUS_EXPORTED) {
                        ++$result['exported'];
                    } else {
                        ++$result['failed'];
                    }
                } catch (ApiReceptionException) {
                    ++$result['failed'];
                }
            }

            return $result;
        } finally {
            $lock->release();
        }
    }

    /**
     * Retries lost deliveries and synchronizes ApiLocal acknowledgements.
     *
     * @return array{queued: int, dispatched: int, deliveryFailed: int, checked: int, available: int, remoteFailed: int}
     */
    public function synchronize(): array
    {
        $lock = $this->locks->createLock('ebp-order-export-sync', 110.0);
        if (!$lock->acquire()) {
            throw new ApiReceptionException('La synchronisation des commandes EBP est déjà en cours.');
        }

        try {
            $result = ['queued' => 0, 'dispatched' => 0, 'deliveryFailed' => 0, 'checked' => 0, 'available' => 0, 'remoteFailed' => 0];
            foreach ($this->orders->awaitingEbpDispatch() as $order) {
                ++$result['queued'];
                try {
                    $this->dispatch($order);
                    ++$result['dispatched'];
                } catch (ApiReceptionException) {
                    ++$result['deliveryFailed'];
                }
            }

            $connection = $this->connections->get();
            foreach ($this->orders->awaitingEbpStatus() as $order) {
                ++$result['checked'];
                try {
                    $remote = $this->api->orderExport($connection, (int) $order->getApiReceptionOrderId());
                    $item = $remote['item'] ?? null;
                    if (($remote['ok'] ?? false) !== true || !is_array($item) || (string) ($item['sourceOrderId'] ?? '') !== (string) $order->getId()) {
                        throw new \DomainException('ApiRecept a retourné un état de commande invalide.');
                    }
                    $before = $order->getEbpTransferStatus();
                    $order->applyEbpTransfer($item);
                    $this->markCommercialOrderAsExportedIfAccepted($order);
                    if ($order->getEbpTransferStatus() === CustomerOrder::EBP_STATUS_AVAILABLE && $before !== CustomerOrder::EBP_STATUS_AVAILABLE) {
                        ++$result['available'];
                    }
                    if ($order->getEbpTransferStatus() === CustomerOrder::EBP_STATUS_FAILED) {
                        ++$result['remoteFailed'];
                    }
                } catch (ApiReceptionException $exception) {
                    // A temporary API outage must not overwrite a previously
                    // accepted remote status; next cron pass will retry it.
                    throw $exception;
                } catch (\Throwable $exception) {
                    $order->recordEbpTransferFailure($this->safeMessage($exception));
                    ++$result['remoteFailed'];
                }
            }
            $this->entityManager->flush();

            return $result;
        } finally {
            $lock->release();
        }
    }

    /** @return array<string, mixed> */
    private function payload(CustomerOrder $order): array
    {
        $customer = $order->getCustomer();
        $lines = [];
        foreach ($order->getLines() as $line) {
            \assert($line instanceof CustomerOrderLine);
            $article = $line->getArticle();
            $lines[] = [
                'articleUniqueId' => $article?->getUniqueId() ?? '',
                'articleCode' => $line->getArticleCode(),
                'designation' => $line->getDesignation() ?? '',
                'quantity' => $line->getQuantity(),
                'unitPriceTtc' => $line->getUnitPriceTtc(),
                'lineTotalTtc' => $line->getLineTotalTtc(),
                'unitPriceBeforeDiscountTtc' => $line->getUnitPriceBeforeDiscountTtc(),
                ...$this->ebpImportPricing($line),
                'discountRate' => $line->getDiscountRate() ?? '0.00',
                'discountAmountTtc' => $line->getDiscountAmountTtc(),
            ];
        }

        return [
            'sourceOrderId' => (string) $order->getId(),
            'orderNumber' => (string) $order->getOrderNumber(),
            'createdAt' => ($order->getCreatedAt() ?? new \DateTimeImmutable())->format(DATE_ATOM),
            'customer' => [
                'ebpUniqueId' => $customer?->getEbpUniqueId() ?? '',
                'customerCode' => $order->getCustomerCode() ?? '',
                'companyName' => $order->getCompanyName() ?? '',
                'email' => $order->getCustomerEmail(),
                'address1' => $order->getAddress1() ?? '',
                'address2' => $order->getAddress2() ?? '',
                'city' => $order->getCity() ?? '',
                'countryCode' => $order->getBillingCountryCode(),
            ],
            'totals' => [
                'subtotalTtc' => $order->getSubtotalTtc(),
                'discountRate' => $order->getDiscountRate(),
                'discountAmountTtc' => $order->getDiscountAmountTtc(),
                'totalTtc' => $order->getTotalTtc(),
            ],
            'lines' => $lines,
        ];
    }

    /**
     * The CSV importer needs the catalogue price before discount in both HT and
     * TTC, as well as the applicable VAT rate. New orders persist these values
     * at checkout. The fallback keeps pre-existing orders exportable when their
     * source article is still available, without manufacturing a VAT rate.
     *
     * @return array{unitPriceBeforeDiscountHt: string, vatRate: string}
     */
    private function ebpImportPricing(CustomerOrderLine $line): array
    {
        $net = $line->getUnitPriceBeforeDiscountHt();
        $vatRate = $line->getVatRate();
        if ($net !== null && $vatRate !== null) {
            return ['unitPriceBeforeDiscountHt' => $net, 'vatRate' => $vatRate];
        }

        $article = $line->getArticle();
        if ($article === null) {
            throw new \DomainException(sprintf(
                'La ligne « %s » ne contient pas les informations HT/TVA nécessaires à l’import EBP.',
                $line->getArticleCode(),
            ));
        }

        $baseTtc = $this->decimal($article->getSalePriceVatIncluded());
        $baseHt = $this->decimal($article->getSalePriceVatExcluded());
        $lineTtc = $this->decimal($line->getUnitPriceBeforeDiscountTtc());
        if ($lineTtc < 0 || $baseTtc < 0 || $baseHt < 0 || ($lineTtc > 0 && ($baseTtc <= 0 || $baseHt <= 0))) {
            throw new \DomainException(sprintf(
                'Le prix HT ou le taux de TVA de l’article « %s » est invalide pour l’import EBP.',
                $line->getArticleCode(),
            ));
        }

        $rate = $lineTtc === 0.0 ? 0.0 : round((($baseTtc / $baseHt) - 1) * 100, 2);
        if ($rate < 0 || $rate > 100) {
            throw new \DomainException(sprintf(
                'Le taux de TVA de l’article « %s » est invalide pour l’import EBP.',
                $line->getArticleCode(),
            ));
        }

        return [
            'unitPriceBeforeDiscountHt' => number_format($lineTtc / (1 + ($rate / 100)), 2, '.', ''),
            'vatRate' => number_format($rate, 2, '.', ''),
        ];
    }

    private function decimal(string $value): float
    {
        $normalized = str_replace(',', '.', trim($value));
        if (!preg_match('/^-?\d{1,12}(?:\.\d{1,4})?$/D', $normalized)) {
            return -1.0;
        }

        return (float) $normalized;
    }

    private function safeMessage(\Throwable $exception): string
    {
        $message = trim($exception->getMessage());

        return $message === '' ? 'La transmission vers ApiRecept sera relancée automatiquement.' : $message;
    }

    /** Marks legacy and newly delivered orders once ApiRecept has accepted them. */
    private function markCommercialOrderAsExportedIfAccepted(CustomerOrder $order): void
    {
        if (
            $order->getStatus() === CustomerOrder::STATUS_NEW
            && in_array($order->getEbpTransferStatus(), [CustomerOrder::EBP_STATUS_EXPORTED, CustomerOrder::EBP_STATUS_AVAILABLE], true)
        ) {
            $order->setStatus(CustomerOrder::STATUS_EXPORTED);
        }
    }
}
