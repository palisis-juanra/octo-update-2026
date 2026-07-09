<?php

namespace App\Services;

use App\Exceptions\PromotionNotApplicableException;
use App\Models\Availability\Availability;
use App\Models\Product;
use App\Models\Rate;
use App\Models\TourCMS\Promotion;
use DateTimeImmutable;
use DateTimeInterface;

class AvailabilityPromotionService
{
    protected JsonLogService $jsonLogService;

    public function __construct(JsonLogService $jsonLogService)
    {
        $this->jsonLogService = $jsonLogService;
    }

    /**
     * Enrich an availability with promotion-derived rates and optionally apply
     * a selected promotion discount to the base pricing.
     *
     * Only promotions applicable to the availability start date will be used.
     *
     * @param Product $product Product containing the applicable promotions.
     * @param Availability $availability Availability to mutate.
     * @param Promotion|null $selectedPromotion Promotion selected by the consumer, if any.
     * @return Availability The same availability instance after being enriched.
     */
    public function enrichAvailabilityWithPromotions(
        Product $product,
        Availability $availability,
        ?Promotion $selectedPromotion = null
    ): Availability {
        
        $applicablePromotions = $this->getApplicablePromotionsForAvailability($product->getPromotions(), $availability);

        if ($selectedPromotion !== null && !$this->isPromotionApplicableToAvailability($selectedPromotion, $availability)) {
            throw new PromotionNotApplicableException($selectedPromotion->getName(), $availability->getLocalDateTimeStart());
        }

        $selectedRateId = $selectedPromotion?->getName();

        $availability->setAvailableRates($this->extractPromotionNames($applicablePromotions));

        $this->buildAvailabilityRates(
            $availability,
            $applicablePromotions,
            $selectedRateId
        );

        if ($selectedPromotion !== null) {
            $this->jsonLogService->info("Applying rate {$selectedPromotion->getName()} discount to availability {$availability->getId()}");
            $this->applyPromotionDiscount($availability, $selectedPromotion);
        }

        return $availability;
    }

    /**
     * Filter promotions and keep only those applicable to the availability start date.
     *
     * @param Promotion[] $promotions Promotions to evaluate.
     * @param Availability $availability Availability whose start date will be used.
     * @return Promotion[] Applicable promotions.
     */
    protected function getApplicablePromotionsForAvailability(array $promotions, Availability $availability): array
    {
        $applicablePromotions = [];
        foreach ($promotions as $promotion) {
            if ($this->isPromotionApplicableToAvailability($promotion, $availability)) {
                $applicablePromotions[] = $promotion;
            }
        }
        return $applicablePromotions;
    }

    /**
     * Determine whether a promotion applies to the given availability start date.
     *
     * A promotion is considered applicable when the availability start date falls
     * within the promotion validity range, including boundary dates.
     *
     * @param Promotion $promotion Promotion to validate.
     * @param Availability $availability Availability to evaluate.
     * @return bool True when the promotion applies to the availability.
     */
    protected function isPromotionApplicableToAvailability(Promotion $promotion, Availability $availability): bool
    {
        $availabilityStartDate = $this->normalizeDate($availability->getLocalDateTimeStart());
        $promotionStartDate = $this->normalizeDate($promotion->getStartDate());
        $promotionEndDate = $this->normalizeDate($promotion->getEndDate());

        if ($promotionStartDate !== null && $availabilityStartDate < $promotionStartDate) {
            $this->jsonLogService->info("Promotion {$promotion->getName()} is not applicable, availability start date is before promotion start date");
            return false;
        }

        if ($promotionEndDate !== null && $availabilityStartDate > $promotionEndDate) {
            $this->jsonLogService->info("Promotion {$promotion->getName()} is not applicable, availability start date is after promotion start date");
            return false;
        }

        return true;
    }

    /**
     * Normalize a date value into a DateTimeImmutable instance.
     *
     * Supported input types:
     * - null
     * - DateTimeInterface
     * - string parseable by DateTimeImmutable
     *
     * @param mixed $date Date value to normalize.
     * @return DateTimeImmutable|null Normalized date, or null when input is null/empty.
     */
    protected function normalizeDate(mixed $date): ?DateTimeImmutable
    {
        if (empty($date)) {
            return null;
        }

        if ($date instanceof DateTimeInterface) {
            return new DateTimeImmutable($date->format('Y-m-d H:i:s'));
        }

        return new DateTimeImmutable((string) $date);
    }

    /**
     * @param Promotion[] $promotions
     * @return string[]
     */
    protected function extractPromotionNames(array $promotions): array
    {
        return array_map(
            static fn (Promotion $promotion) => $promotion->getName(),
            $promotions
        );
    }

    /**
     * @param Promotion[] $promotions
     * @return void
     */
    protected function buildAvailabilityRates(
        Availability $availability,
        array $promotions,
        ?string $selectedRateId
    ): void {
        $this->buildAvailabilityPricingRates($availability, $promotions, $selectedRateId);
        $this->buildAvailabilityUnitPricingRates($availability, $promotions, $selectedRateId);
    }

    /**
     * @param Promotion[] $promotions
     * @return void
     */
    protected function buildAvailabilityPricingRates(
        Availability $availability,
        array $promotions,
        ?string $selectedRateId
    ): void {
        $pricing = $availability->getPricing();
        $pricing->setRateId($selectedRateId);

        $retailPrice = $pricing->getRetail();
        $netPrice = $pricing->getNet();

        $rates = [];
        foreach ($promotions as $promotion) {
            $discount = (float) $promotion->getDiscount();

            $rates[] = new Rate(
                $promotion->getName(),
                $this->applyDiscount($retailPrice, $discount),
                $this->applyDiscount($netPrice, $discount)
            );
        }

        $pricing->setRates($rates);
    }

    /**
     * @param Promotion[] $promotions
     * @return void
     */
    protected function buildAvailabilityUnitPricingRates(
        Availability $availability,
        array $promotions,
        ?string $selectedRateId
    ): void {
        foreach ($availability->getUnitPricing() as $unitPricing) {
            $unitPricing->setRateId($selectedRateId);

            $rates = [];
            foreach ($promotions as $promotion) {
                $discount = (float) $promotion->getDiscount();

                $rates[] = new Rate(
                    $promotion->getName(),
                    $this->applyDiscount($unitPricing->getRetailPrice(), $discount),
                    $this->applyDiscount($unitPricing->getNetPrice(), $discount)
                );
            }

            $unitPricing->setRates($rates);
        }
    }

    /**
     * Apply a selected promotion discount directly to the base pricing of an availability.
     *
     * @param Availability $availability Availability to mutate.
     * @param Promotion $promotion Promotion whose discount will be applied.
     * @return void
     */
    protected function applyPromotionDiscount(
        Availability $availability,
        Promotion $promotion
    ): void {
        $discount = (float) $promotion->getDiscount();

        $pricing = $availability->getPricing();
        $pricing->setRetail($this->applyDiscount($pricing->getRetail(), $discount));
        $pricing->setNet($this->applyDiscount($pricing->getNet() ?? $pricing->getRetail(), $discount));

        foreach ($availability->getUnitPricing() as $unitPricing) {
            $unitPricing->setRetailPrice(
                $this->applyDiscount($unitPricing->getRetailPrice(), $discount)
            );
            $unitPricing->setNetPrice(
                $this->applyDiscount($unitPricing->getNetPrice(), $discount)
            );
        }
    }

    /**
     * Apply a percentage discount to a price.
     *
     * @param int $price Original price.
     * @param float $discount Discount percentage.
     * @return int Discounted and rounded price.
     */
    protected function applyDiscount(int $price, float $discount): int
    {
        return (int) round($price * (1 - ($discount / 100)));
    }
}