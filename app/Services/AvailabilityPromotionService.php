<?php

namespace App\Services;

use App\Facades\JsonLog;
use App\Models\Availability\Availability;
use App\Models\Product;
use App\Models\Rate;
use App\Models\TourCMS\Promotion;

/**
 * Service responsible for enriching availability objects with promotion-based rates.
 *
 * This service does not fetch promotions from external sources. Instead, it receives
 * already available Promotion objects and applies them to an Availability instance by:
 * - populating the list of available rate identifiers
 * - generating derived Rate objects for pricing and unit pricing
 * - optionally applying the selected promotion discount directly to the base pricing
 */
class AvailabilityPromotionService
{
    /**
     * Enrich an availability with promotion-derived rates and optionally apply
     * a selected promotion discount to the base pricing.
     *
     * The method:
     * - extracts promotion names and sets them as available rates
     * - generates rate alternatives for availability pricing
     * - generates rate alternatives for unit pricing
     * - if a selected promotion is provided, applies its discount directly
     *   to the current availability prices
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
        $promotions = $product->getPromotions();
        $selectedRateId = $selectedPromotion?->getName();

        $availability->setAvailableRates($this->extractPromotionNames($promotions));

        $this->buildAvailabilityRates(
            $availability,
            $promotions,
            $selectedRateId
        );

        if ($selectedPromotion !== null) {
            JsonLog::info(
                "Applying rate {$selectedPromotion->getName()} discount to availability {$availability->getId()}"
            );

            $this->applyPromotionDiscount(
                $availability,
                $selectedPromotion
            );
        }

        return $availability;
    }

    /**
     * Extract promotion names from a list of Promotion objects.
     *
     * This is used to populate the availability availableRates field.
     *
     * @param Promotion[] $promotions List of promotions.
     * @return string[] Promotion names.
     */
    protected function extractPromotionNames(array $promotions): array
    {
        $promotionNames = [];
        foreach ($promotions as $promotion) {
            $promotionNames[] = $promotion->getName();
        }

        return $promotionNames;
    }

    /**
     * Build all promotion-derived rates for the given availability.
     *
     * This includes both top-level pricing rates and unit pricing rates.
     *
     * @param Availability $availability Availability to enrich.
     * @param Promotion[] $promotions Promotions to use for rate generation.
     * @param string|null $selectedRateId Selected rate identifier, if any.
     * @return void
     */
    protected function buildAvailabilityRates(
        Availability $availability,
        array $promotions,
        ?string $selectedRateId
    ): void {
        $this->buildAvailabilityPricingRates(
            $availability,
            $promotions,
            $selectedRateId
        );

        $this->buildAvailabilityUnitPricingRates(
            $availability,
            $promotions,
            $selectedRateId
        );
    }

    /**
     * Build rate alternatives for the main availability pricing object.
     *
     * Each promotion produces a derived Rate instance containing the discounted
     * retail and net values based on the current pricing values.
     *
     * The selected rate id is also assigned to the pricing object.
     *
     * @param Availability $availability Availability whose pricing will be enriched.
     * @param Promotion[] $promotions Promotions to convert into rates.
     * @param string|null $selectedRateId Selected rate identifier, if any.
     * @return void
     */
    protected function buildAvailabilityPricingRates(Availability $availability, array $promotions, ?string $selectedRateId): void
    {
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
     * Build rate alternatives for each unit pricing entry in the availability.
     *
     * Each unit pricing element receives:
     * - the selected rate id
     * - a list of Rate instances derived from the provided promotions
     *
     * @param Availability $availability Availability whose unit pricing will be enriched.
     * @param Promotion[] $promotions Promotions to convert into unit-level rates.
     * @param string|null $selectedRateId Selected rate identifier, if any.
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
     * This mutates:
     * - the main pricing object
     * - all unit pricing entries
     *
     * This method should only be called when a promotion has actually been selected
     * by the consumer and the final response pricing must reflect that selection.
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
        $pricing->setNet($this->applyDiscount($pricing->getNet(), $discount));

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
     * The result is rounded and cast to integer to match the expected pricing format.
     *
     * Example:
     * - price: 1000
     * - discount: 10
     * - result: 900
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