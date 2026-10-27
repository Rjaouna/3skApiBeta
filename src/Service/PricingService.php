<?php

namespace App\Service;

use App\Entity\Article;
use App\Entity\User;
use App\Repository\CategoryReductionRepository;
use App\Repository\UserFamilyDiscountRepository;

class PricingService
{
    /**
     * @var array<int, array<string, float>>
     */
    private array $userDiscountMapCache = [];

    /**
     * @var array<string, float>|null
     */
    private ?array $categoryDiscountMapCache = null;

    public function __construct(
        private readonly UserFamilyDiscountRepository $userFamilyDiscountRepository,
        private readonly CategoryReductionRepository $categoryReductionRepository,
    ) {
    }

    public function computeArticlePricing(Article $article, ?User $user = null): array
    {
        $unitPriceBeforeDiscountTtc = $this->toFloat($article->getSalePriceVatIncluded());
        $unitPriceBeforeDiscountHt = $this->toFloat($article->getSalePriceVatExcluded());
        $vatRate = $this->vatRate($unitPriceBeforeDiscountTtc, $unitPriceBeforeDiscountHt);

        $categoryReduction = $this->getCategoryReductionForArticle($article);
        $baseReduction = $categoryReduction ?? $this->normalizeRate($article->getReduction());
        $personalReduction = $this->getPersonalReductionForArticle($article, $user);
        $globalReduction = $this->clampRate($baseReduction + $personalReduction);

        $unitPriceFinalTtc = $this->applyRate($unitPriceBeforeDiscountTtc, $globalReduction);
        $unitPriceFinalHt = $this->applyRate($unitPriceBeforeDiscountHt, $globalReduction);

        return [
            'baseReduction' => $baseReduction,
            'personalReduction' => $personalReduction,
            'globalReduction' => $globalReduction,
            'globalReductionFormatted' => $globalReduction > 0 ? $this->formatRate($globalReduction) : null,
            'reductionLabel' => $globalReduction > 0 ? '-' . $this->formatRate($globalReduction) . '%' : null,
            'hasReduction' => $globalReduction > 0,

            'unitPriceBeforeDiscountTtc' => $unitPriceBeforeDiscountTtc,
            'unitPriceBeforeDiscountTtcFormatted' => $this->formatMoney($unitPriceBeforeDiscountTtc),

            'unitPriceBeforeDiscountHt' => $unitPriceBeforeDiscountHt,
            'unitPriceBeforeDiscountHtFormatted' => $this->formatMoney($unitPriceBeforeDiscountHt),

            'vatRate' => $vatRate,
            'vatRateFormatted' => $this->formatMoney($vatRate),

            'unitPriceFinalTtc' => $unitPriceFinalTtc,
            'unitPriceFinalTtcFormatted' => $this->formatMoney($unitPriceFinalTtc),

            'unitPriceFinalHt' => $unitPriceFinalHt,
            'unitPriceFinalHtFormatted' => $this->formatMoney($unitPriceFinalHt),
        ];
    }

    public function computeCartLinePricing(
        Article $article,
        int $quantity,
        ?User $user = null,
        ?string $storedBaseUnitPriceTtc = null
    ): array {
        $quantity = max(0, $quantity);

        $articlePricing = $this->computeArticlePricing($article, $user);

        $unitPriceBeforeDiscountTtc = $this->toFloat($storedBaseUnitPriceTtc);
        if ($unitPriceBeforeDiscountTtc <= 0) {
            $unitPriceBeforeDiscountTtc = $articlePricing['unitPriceBeforeDiscountTtc'];
        }

        $globalReduction = (float) $articlePricing['globalReduction'];

        $unitPriceFinalTtc = $this->applyRate($unitPriceBeforeDiscountTtc, $globalReduction);
        $unitPriceBeforeDiscountHt = $this->removeVat($unitPriceBeforeDiscountTtc, (float) $articlePricing['vatRate']);
        $lineTotalBeforeDiscount = round($unitPriceBeforeDiscountTtc * $quantity, 2);
        $lineDiscountAmount = round($lineTotalBeforeDiscount * ($globalReduction / 100), 2);
        $lineTotalFinal = round($lineTotalBeforeDiscount - $lineDiscountAmount, 2);

        return array_merge($articlePricing, [
            'quantity' => $quantity,

            'unitPriceBeforeDiscount' => $unitPriceBeforeDiscountTtc,
            'unitPriceBeforeDiscountFormatted' => $this->formatMoney($unitPriceBeforeDiscountTtc),

            'unitPriceBeforeDiscountHt' => $unitPriceBeforeDiscountHt,
            'unitPriceBeforeDiscountHtFormatted' => $this->formatMoney($unitPriceBeforeDiscountHt),

            'unitPriceFinal' => $unitPriceFinalTtc,
            'unitPriceFinalFormatted' => $this->formatMoney($unitPriceFinalTtc),

            'lineTotalBeforeDiscount' => $lineTotalBeforeDiscount,
            'lineTotalBeforeDiscountFormatted' => $this->formatMoney($lineTotalBeforeDiscount),

            'lineDiscountAmount' => $lineDiscountAmount,
            'lineDiscountAmountFormatted' => $this->formatMoney($lineDiscountAmount),

            'lineTotalFinal' => $lineTotalFinal,
            'lineTotalFinalFormatted' => $this->formatMoney($lineTotalFinal),
        ]);
    }

    public function buildSummaryReductionText(array $formattedRates): ?string
    {
        $formattedRates = array_values(array_unique(array_filter(
            array_map(
                static fn ($value) => trim((string) $value),
                $formattedRates
            ),
            static fn (string $value) => $value !== '' && $value !== '0' && $value !== '0.00'
        )));

        if ($formattedRates === []) {
            return null;
        }

        if (count($formattedRates) === 1) {
            return '-' . $formattedRates[0] . '%';
        }

        return 'Auto';
    }

    public function formatMoney(float $value): string
    {
        return number_format(round($value, 2), 2, '.', '');
    }

    public function formatRate(float $value): string
    {
        return rtrim(rtrim(number_format(round($value, 2), 2, '.', ''), '0'), '.');
    }

    public function normalizeRate(string|float|int|null $value): float
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return 0.0;
        }

        $raw = str_replace('%', '', $raw);
        $raw = str_replace(',', '.', $raw);

        if (!is_numeric($raw)) {
            return 0.0;
        }

        return $this->clampRate((float) $raw);
    }

    private function getPersonalReductionForArticle(Article $article, ?User $user): float
    {
        if (!$user instanceof User || $user->getId() === null) {
            return 0.0;
        }

        $familyName = $article->getFamilyName();
        if (!is_string($familyName) || trim($familyName) === '') {
            return 0.0;
        }

        $map = $this->getUserDiscountMap($user);
        $key = $this->normalizeFamilyKey($familyName);

        return $map[$key] ?? 0.0;
    }

    private function getCategoryReductionForArticle(Article $article): ?float
    {
        $familyName = $article->getFamilyName();
        if (!is_string($familyName) || trim($familyName) === '') {
            return null;
        }

        if ($this->categoryDiscountMapCache === null) {
            $this->categoryDiscountMapCache = [];

            foreach ($this->categoryReductionRepository->findDiscountRateMap() as $family => $rate) {
                $this->categoryDiscountMapCache[$this->normalizeFamilyKey($family)] = $this->normalizeRate($rate);
            }
        }

        $key = $this->normalizeFamilyKey($familyName);

        return $this->categoryDiscountMapCache[$key] ?? null;
    }

    /**
     * @return array<string, float>
     */
    private function getUserDiscountMap(User $user): array
    {
        $userId = (int) $user->getId();

        if (isset($this->userDiscountMapCache[$userId])) {
            return $this->userDiscountMapCache[$userId];
        }

        $map = [];

        foreach ($this->userFamilyDiscountRepository->findByUserOrdered($user) as $discount) {
            $familyName = $discount->getFamilyName();
            if ($familyName === '') {
                continue;
            }

            $map[$this->normalizeFamilyKey($familyName)] = $this->normalizeRate($discount->getDiscountRate());
        }

        $this->userDiscountMapCache[$userId] = $map;

        return $map;
    }

    private function normalizeFamilyKey(?string $familyName): string
    {
        $familyName = preg_replace('/\s+/u', ' ', trim((string) $familyName)) ?? '';

        return mb_strtoupper($familyName, 'UTF-8');
    }

    private function toFloat(string|float|int|null $value): float
    {
        $raw = str_replace(',', '.', trim((string) $value));
        if ($raw === '' || !is_numeric($raw)) {
            return 0.0;
        }

        return round((float) $raw, 2);
    }

    private function clampRate(float $rate): float
    {
        if ($rate < 0) {
            $rate = 0;
        }

        if ($rate > 100) {
            $rate = 100;
        }

        return round($rate, 2);
    }

    private function applyRate(float $amount, float $rate): float
    {
        return round($amount * (1 - ($rate / 100)), 2);
    }

    private function vatRate(float $ttc, float $ht): float
    {
        if ($ttc <= 0 || $ht <= 0) {
            return 0.0;
        }

        return round(max(0.0, (($ttc / $ht) - 1) * 100), 2);
    }

    private function removeVat(float $ttc, float $vatRate): float
    {
        return round($ttc / (1 + (max(0.0, $vatRate) / 100)), 2);
    }
}
