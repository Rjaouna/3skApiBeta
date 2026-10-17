<?php

namespace App\Service;

use App\Repository\ArticleRepository;
use App\Repository\CategoryReductionRepository;
use App\Repository\CartItemRepository;
use App\Repository\CartRepository;
use App\Repository\CustomerOrderLineRepository;
use App\Repository\CustomerOrderRepository;

class AdminDashboardService
{
    private \DateTimeZone $timezone;

    public function __construct(
        private readonly CartRepository $cartRepository,
        private readonly CartItemRepository $cartItemRepository,
        private readonly CustomerOrderRepository $customerOrderRepository,
        private readonly CustomerOrderLineRepository $customerOrderLineRepository,
        private readonly ArticleRepository $articleRepository,
        private readonly CategoryReductionRepository $categoryReductionRepository,
    ) {
        $this->timezone = new \DateTimeZone('Europe/Paris');
    }

    public function getDashboardPayload(): array
    {
        $now = new \DateTimeImmutable('now', $this->timezone);

        $ordersSince = $now
            ->modify('first day of this month')
            ->setTime(0, 0, 0)
            ->modify('-11 months');

        $orderRows = $this->customerOrderRepository->findDashboardOrdersSince($ordersSince);
        $categoryRows = $this->customerOrderLineRepository->findDashboardCategoryRowsSince($ordersSince);
        $stockRows = $this->articleRepository->findDashboardStockRows();

        return [
            'generatedAt' => $now->format(\DateTimeInterface::ATOM),
            'cart' => $this->buildCartStats(),
            'orders' => $this->buildOrderStats($orderRows, $now),
            'categories' => $this->buildCategoryStats($categoryRows, $now),
            'stock' => $this->buildStockStats($stockRows),
        ];
    }

    private function buildCartStats(): array
    {
        $openCartsCount = $this->cartRepository->countOpenCartsWithUser();
        $summary = $this->cartItemRepository->getDashboardSummary();
        $byCustomer = $this->cartItemRepository->getDashboardByCustomer(10);
        $byArticle = $this->cartItemRepository->getDashboardByArticle(10);

        return [
            'summary' => [
                'openCartsCount' => $openCartsCount,
                'customersCount' => (int) ($summary['customersCount'] ?? 0),
                'uniqueArticlesCount' => (int) ($summary['uniqueArticlesCount'] ?? 0),
                'itemsQty' => (int) ($summary['itemsQty'] ?? 0),
                'linesCount' => (int) ($summary['linesCount'] ?? 0),
            ],
            'byCustomer' => array_map(function (array $row): array {
                return [
                    'userId' => (int) ($row['userId'] ?? 0),
                    'email' => (string) ($row['email'] ?? ''),
                    'companyName' => (string) ($row['companyName'] ?? ''),
                    'cartsCount' => (int) ($row['cartsCount'] ?? 0),
                    'linesCount' => (int) ($row['linesCount'] ?? 0),
                    'itemsQty' => (int) ($row['itemsQty'] ?? 0),
                ];
            }, $byCustomer),
            'byArticle' => array_map(function (array $row): array {
                return [
                    'code' => (string) ($row['code'] ?? ''),
                    'designation' => (string) ($row['designation'] ?? ''),
                    'familyName' => (string) ($row['familyName'] ?? ''),
                    'cartsCount' => (int) ($row['cartsCount'] ?? 0),
                    'customersCount' => (int) ($row['customersCount'] ?? 0),
                    'itemsQty' => (int) ($row['itemsQty'] ?? 0),
                ];
            }, $byArticle),
        ];
    }

    private function buildOrderStats(array $rows, \DateTimeImmutable $now): array
    {
        $dayStart = $now->setTime(0, 0, 0);
        $weekStart = $now->modify('monday this week')->setTime(0, 0, 0);
        $monthStart = $now->modify('first day of this month')->setTime(0, 0, 0);

        $dailySeries = $this->initDailySeries($now);
        $weeklySeries = $this->initWeeklySeries($now);
        $monthlySeries = $this->initMonthlySeries($now);

        $daySummary = $this->newMetricBucket('Aujourd’hui');
        $weekSummary = $this->newMetricBucket('Cette semaine');
        $monthSummary = $this->newMetricBucket('Ce mois');

        foreach ($rows as $row) {
            $createdAt = $this->toDateTimeImmutable($row['createdAt'] ?? null);
            if (!$createdAt) {
                continue;
            }

            $subtotal = $this->toMoneyFloat($row['subtotalTtc'] ?? 0);
            $total = $this->toMoneyFloat($row['totalTtc'] ?? 0);
            $discount = max(0.0, round($subtotal - $total, 2));

            if ($createdAt >= $dayStart) {
                $this->appendMetric($daySummary, $subtotal, $discount, $total);
            }

            if ($createdAt >= $weekStart) {
                $this->appendMetric($weekSummary, $subtotal, $discount, $total);
            }

            if ($createdAt >= $monthStart) {
                $this->appendMetric($monthSummary, $subtotal, $discount, $total);
            }

            $dayKey = $createdAt->format('Y-m-d');
            if (isset($dailySeries[$dayKey])) {
                $this->appendMetric($dailySeries[$dayKey], $subtotal, $discount, $total);
            }

            $weekKey = $createdAt->format('o-\WW');
            if (isset($weeklySeries[$weekKey])) {
                $this->appendMetric($weeklySeries[$weekKey], $subtotal, $discount, $total);
            }

            $monthKey = $createdAt->format('Y-m');
            if (isset($monthlySeries[$monthKey])) {
                $this->appendMetric($monthlySeries[$monthKey], $subtotal, $discount, $total);
            }
        }

        return [
            'summary' => [
                'day' => $this->finalizeMetricBucket($daySummary),
                'week' => $this->finalizeMetricBucket($weekSummary),
                'month' => $this->finalizeMetricBucket($monthSummary),
            ],
            'series' => [
                'daily' => $this->finalizeMetricSeries($dailySeries),
                'weekly' => $this->finalizeMetricSeries($weeklySeries),
                'monthly' => $this->finalizeMetricSeries($monthlySeries),
            ],
        ];
    }

    private function buildCategoryStats(array $rows, \DateTimeImmutable $now): array
    {
        $dayStart = $now->setTime(0, 0, 0);
        $weekStart = $now->modify('monday this week')->setTime(0, 0, 0);
        $monthStart = $now->modify('first day of this month')->setTime(0, 0, 0);

        return [
            'topDay' => $this->aggregateCategories($rows, $dayStart, 10),
            'topWeek' => $this->aggregateCategories($rows, $weekStart, 10),
            'topMonth' => $this->aggregateCategories($rows, $monthStart, 10),
        ];
    }

    private function buildStockStats(array $rows): array
    {
        $articlesCount = 0;
        $inStockArticlesCount = 0;
        $outOfStockArticlesCount = 0;
        $totalUnits = 0;

        $valueTtcWithoutReduction = 0.0;
        $valueTtcWithReduction = 0.0;
        $valueHtWithoutReduction = 0.0;
        $valueHtWithReduction = 0.0;

        $familyMap = [];
        $categoryReductionMap = $this->categoryReductionRepository->findDiscountRateMap();

        foreach ($rows as $row) {
            $articlesCount++;

            $stock = max(0, (int) ($row['realStock'] ?? 0));
            $familyName = trim((string) ($row['familyName'] ?? ''));
            $familyName = $familyName !== '' ? $familyName : 'Non classée';
            $reduction = $this->normalizeReduction(
                $categoryReductionMap[$this->normalizeFamilyKey($familyName)] ?? $row['reduction'] ?? null
            );

            $unitTtc = $this->toMoneyFloat($row['salePriceVatIncluded'] ?? 0);
            $unitHt = $this->toMoneyFloat($row['salePriceVatExcluded'] ?? 0);

            if ($stock > 0) {
                $inStockArticlesCount++;
            } else {
                $outOfStockArticlesCount++;
            }

            $totalUnits += $stock;

            $articleTtcWithoutReduction = $stock * $unitTtc;
            $articleHtWithoutReduction = $stock * $unitHt;

            $articleTtcWithReduction = $articleTtcWithoutReduction * (1 - ($reduction / 100));
            $articleHtWithReduction = $articleHtWithoutReduction * (1 - ($reduction / 100));

            $valueTtcWithoutReduction += $articleTtcWithoutReduction;
            $valueTtcWithReduction += $articleTtcWithReduction;
            $valueHtWithoutReduction += $articleHtWithoutReduction;
            $valueHtWithReduction += $articleHtWithReduction;

            if (!isset($familyMap[$familyName])) {
                $familyMap[$familyName] = [
                    'familyName' => $familyName,
                    'articlesCount' => 0,
                    'totalUnits' => 0,
                    'valueTtcWithoutReduction' => 0.0,
                    'valueTtcWithReduction' => 0.0,
                ];
            }

            $familyMap[$familyName]['articlesCount']++;
            $familyMap[$familyName]['totalUnits'] += $stock;
            $familyMap[$familyName]['valueTtcWithoutReduction'] += $articleTtcWithoutReduction;
            $familyMap[$familyName]['valueTtcWithReduction'] += $articleTtcWithReduction;
        }

        $topFamilies = array_values($familyMap);

        usort($topFamilies, static function (array $a, array $b): int {
            $cmp = $b['valueTtcWithReduction'] <=> $a['valueTtcWithReduction'];
            if ($cmp !== 0) {
                return $cmp;
            }

            $cmp = $b['totalUnits'] <=> $a['totalUnits'];
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcmp((string) $a['familyName'], (string) $b['familyName']);
        });

        $topFamilies = array_slice($topFamilies, 0, 10);

        $topFamilies = array_map(function (array $row): array {
            return [
                'familyName' => (string) $row['familyName'],
                'articlesCount' => (int) $row['articlesCount'],
                'totalUnits' => (int) $row['totalUnits'],
                'valueTtcWithoutReduction' => $this->roundMoney($row['valueTtcWithoutReduction']),
                'valueTtcWithReduction' => $this->roundMoney($row['valueTtcWithReduction']),
            ];
        }, $topFamilies);

        return [
            'summary' => [
                'articlesCount' => $articlesCount,
                'inStockArticlesCount' => $inStockArticlesCount,
                'outOfStockArticlesCount' => $outOfStockArticlesCount,
                'totalUnits' => $totalUnits,
                'valueTtcWithoutReduction' => $this->roundMoney($valueTtcWithoutReduction),
                'valueTtcWithReduction' => $this->roundMoney($valueTtcWithReduction),
                'valueTtcReductionAmount' => $this->roundMoney($valueTtcWithoutReduction - $valueTtcWithReduction),
                'valueHtWithoutReduction' => $this->roundMoney($valueHtWithoutReduction),
                'valueHtWithReduction' => $this->roundMoney($valueHtWithReduction),
                'valueHtReductionAmount' => $this->roundMoney($valueHtWithoutReduction - $valueHtWithReduction),
            ],
            'topFamilies' => $topFamilies,
        ];
    }

    private function aggregateCategories(array $rows, ?\DateTimeImmutable $from, int $limit): array
    {
        $map = [];

        foreach ($rows as $row) {
            $createdAt = $this->toDateTimeImmutable($row['createdAt'] ?? null);
            if (!$createdAt) {
                continue;
            }

            if ($from !== null && $createdAt < $from) {
                continue;
            }

            $category = trim((string) ($row['familyName'] ?? ''));
            $category = $category !== '' ? $category : 'Non classée';

            if (!isset($map[$category])) {
                $map[$category] = [
                    'category' => $category,
                    'orders' => [],
                    'quantity' => 0,
                    'subtotal' => 0.0,
                    'discount' => 0.0,
                    'total' => 0.0,
                ];
            }

            $orderId = (int) ($row['orderId'] ?? 0);
            if ($orderId > 0) {
                $map[$category]['orders'][$orderId] = true;
            }

            $map[$category]['quantity'] += (int) ($row['quantity'] ?? 0);
            $map[$category]['subtotal'] += $this->toMoneyFloat($row['subtotalTtc'] ?? 0);
            $map[$category]['discount'] += $this->toMoneyFloat($row['discountTtc'] ?? 0);
            $map[$category]['total'] += $this->toMoneyFloat($row['totalTtc'] ?? 0);
        }

        $items = [];
        foreach ($map as $row) {
            $items[] = [
                'category' => (string) $row['category'],
                'ordersCount' => count($row['orders']),
                'quantity' => (int) $row['quantity'],
                'subtotal' => $this->roundMoney($row['subtotal']),
                'discount' => $this->roundMoney($row['discount']),
                'total' => $this->roundMoney($row['total']),
            ];
        }

        usort($items, static function (array $a, array $b): int {
            $cmp = $b['total'] <=> $a['total'];
            if ($cmp !== 0) {
                return $cmp;
            }

            $cmp = $b['quantity'] <=> $a['quantity'];
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcmp((string) $a['category'], (string) $b['category']);
        });

        return array_slice($items, 0, $limit);
    }

    private function initDailySeries(\DateTimeImmutable $now): array
    {
        $start = $now->setTime(0, 0, 0)->modify('-29 days');
        $series = [];

        for ($i = 0; $i < 30; $i++) {
            $date = $start->modify(sprintf('+%d days', $i));
            $series[$date->format('Y-m-d')] = $this->newMetricBucket($date->format('d/m'));
        }

        return $series;
    }

    private function initWeeklySeries(\DateTimeImmutable $now): array
    {
        $start = $now->modify('monday this week')->setTime(0, 0, 0)->modify('-11 weeks');
        $series = [];

        for ($i = 0; $i < 12; $i++) {
            $date = $start->modify(sprintf('+%d weeks', $i));
            $series[$date->format('o-\WW')] = $this->newMetricBucket(
                sprintf('S%s %s', $date->format('W'), $date->format('o'))
            );
        }

        return $series;
    }

    private function initMonthlySeries(\DateTimeImmutable $now): array
    {
        $start = $now->modify('first day of this month')->setTime(0, 0, 0)->modify('-11 months');
        $series = [];

        for ($i = 0; $i < 12; $i++) {
            $date = $start->modify(sprintf('+%d months', $i));
            $series[$date->format('Y-m')] = $this->newMetricBucket($date->format('m/Y'));
        }

        return $series;
    }

    private function newMetricBucket(string $label): array
    {
        return [
            'label' => $label,
            'ordersCount' => 0,
            'subtotal' => 0.0,
            'discount' => 0.0,
            'total' => 0.0,
        ];
    }

    private function appendMetric(array &$bucket, float $subtotal, float $discount, float $total): void
    {
        $bucket['ordersCount']++;
        $bucket['subtotal'] += $subtotal;
        $bucket['discount'] += $discount;
        $bucket['total'] += $total;
    }

    private function finalizeMetricBucket(array $bucket): array
    {
        return [
            'label' => (string) ($bucket['label'] ?? ''),
            'ordersCount' => (int) ($bucket['ordersCount'] ?? 0),
            'subtotal' => $this->roundMoney($bucket['subtotal'] ?? 0),
            'discount' => $this->roundMoney($bucket['discount'] ?? 0),
            'total' => $this->roundMoney($bucket['total'] ?? 0),
        ];
    }

    private function finalizeMetricSeries(array $series): array
    {
        return array_map(fn (array $bucket) => $this->finalizeMetricBucket($bucket), array_values($series));
    }

    private function toDateTimeImmutable(mixed $value): ?\DateTimeImmutable
    {
        if ($value instanceof \DateTimeImmutable) {
            return $value->setTimezone($this->timezone);
        }

        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value)->setTimezone($this->timezone);
        }

        if (is_string($value) && trim($value) !== '') {
            try {
                return new \DateTimeImmutable($value, $this->timezone);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    private function toMoneyFloat(mixed $value): float
    {
        $value = trim((string) $value);
        if ($value === '') {
            return 0.0;
        }

        $value = str_replace(',', '.', $value);

        if (!is_numeric($value)) {
            return 0.0;
        }

        return round((float) $value, 2);
    }

    private function normalizeReduction(mixed $value): float
    {
        $value = trim((string) $value);
        if ($value === '') {
            return 0.0;
        }

        $value = str_replace('%', '', $value);
        $value = str_replace(',', '.', $value);

        if (!is_numeric($value)) {
            return 0.0;
        }

        $number = (float) $value;
        if ($number < 0) {
            $number = 0.0;
        }
        if ($number > 100) {
            $number = 100.0;
        }

        return round($number, 2);
    }

    private function normalizeFamilyKey(string $familyName): string
    {
        $familyName = preg_replace('/\s+/u', ' ', trim($familyName)) ?? '';

        return mb_strtoupper($familyName, 'UTF-8');
    }

    private function roundMoney(float $value): float
    {
        return round($value, 2);
    }
}
