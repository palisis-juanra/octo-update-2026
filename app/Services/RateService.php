<?php

namespace App\Services;

use App\Exceptions\InvalidRateIdException;
use App\Facades\JsonLog;
use App\Models\Availability\Availability;
use App\Models\Rate;
use App\Models\TourCMS\Promotion;

/**
 * Handles common functionality for bookingcom/rates capability logic
 */
class RateService
{
    protected TourCMSService $tourCMSService;

    public function __construct(TourCMSService $tourCMSService)
    {
        $this->tourCMSService = $tourCMSService;
    }

    /**
     * @param int $tourId
     * @param null|string $rateId
     * @throws InvalidRateIdException
     * @return bool
     */
    public function validateRateId(int $tourId, ?string $rateId): bool
    {
        if (empty($rateId)) {
            return true;
        }

        $validPromotions = $this->getPromotionsForTour($tourId);
        $validPromotionsNames = array_map(function(Promotion $promotion) { return $promotion->getName(); }, $validPromotions);

        if (!in_array($rateId, $validPromotionsNames)) {
            throw new InvalidRateIdException($rateId);
        }

        return true;
    }

    /**
     * Fetch promotions for a certain tour
     * @param int $tourId
     * @return Promotion[]
     */
    public function getPromotionsForTour(int $tourId): array
    {
        $tourPromotionsXML = $this->tourCMSService->getTourPromotions($tourId);
        if (empty($tourPromotionsXML->promotions)) {
            return [];
        }

        $promotions = [];
        $promotionsData = XMLService::getArrayFromXmlNode($tourPromotionsXML->promotions, 'promotion') ?? [];
        foreach ($promotionsData as $promotionData) {
            $promotions[] = Promotion::fromXML($promotionData);
        }

        return $promotions;
    }

    /**
     * Add promotion/rate and apply discount to the availability
     * @param Availability[] $availabilities
     * @param Promotion[] $promotions
     */
    public function addPromotionsToAvailability(Availability $availability, array $promotions, ?string $rateId): Availability
    {

        $promotionToApply = null;
        $availableRateNames = [];
        foreach ($promotions as $promotion) {
            $availableRateNames[] = $promotion->getName();
            if ($promotion->getName() === $rateId) {
                $promotionToApply = $promotion;
            }
        }
        $availability->setAvailableRates($availableRateNames);   

        $this->updateAvailabilityPricing($availability, $promotions, $rateId);
        $this->updateAvailabilityUnitPricing($availability, $promotions, $rateId);

        if (null !== $promotionToApply) {
            JsonLog::info("Applying rate {$rateId} discount to availability {$availability->getId()}");
            $this->applyPromotionDiscount($availability, $promotionToApply);
        }

        return $availability;
    }

    /* PROTECTED METHODS */

    /**
     * Apply a promotion to an availability
     * @param Availability $availability
     * @param Promotion $promotion
     * @return void
     */
    protected function applyPromotionDiscount(Availability $availability, Promotion $promotion): void
    {
        $discount = (float) $promotion->getDiscount();
        $pricing = $availability->getPricing();

        $newRetailPrice = (int) round($pricing->getRetail() * (1 - $discount / 100));
        $newNetPrice = (int) round($pricing->getNet() * (1 - $discount / 100));

        $pricing->setRetail($newRetailPrice);
        $pricing->setNet($newNetPrice);

        $unitPricingArray = $availability->getUnitPricing();
        foreach ($unitPricingArray as $unitPricing) {
            $newRetailPrice = (int) round($unitPricing->getRetailPrice() * (1 - $discount / 100));
            $newNetPrice = (int) round($unitPricing->getNetPrice() * (1 - $discount / 100));

            $unitPricing->setRetailPrice($newRetailPrice);
            $unitPricing->setNetPrice($newNetPrice);
        }
    }

    /**
     * Update availability unit pricing based on rate selected
     * @param Availability $availability
     * @param array $promotions
     * @param mixed $rateId
     * @return void
     */
    protected function updateAvailabilityUnitPricing(Availability $availability, array $promotions, ?string $rateId): void
    {
        $unitPricingArray = $availability->getUnitPricing();
        foreach ($unitPricingArray as $unitPricing) {
            
            $unitPricing->setRateId($rateId);
            $rates = [];
            foreach ($promotions as $promotion) {
                $discount = $promotion->getDiscount();
                $rateRetailPrice = (int) round($unitPricing->getRetailPrice() * (1 - $discount / 100));
                $rateNetPrice = (int) round($unitPricing->getNetPrice() * (1 - $discount / 100));

                $rates[] = new Rate(
                    $promotion->getName(),
                    $rateRetailPrice,
                    $rateNetPrice
                );
            }
            $unitPricing->setRates($rates);
        }
    }

    /**
     * Update availability pricing based on rate selected
     * @param Availability $availability
     * @param array $promotions
     * @param mixed $rateId
     * @return void
     */
    protected function updateAvailabilityPricing(Availability $availability, array $promotions, ?string $rateId): void
    {
        $rates = [];
        $availabilityPricing = $availability->getPricing();
        $availabilityPricing->setRateId($rateId);
        
        $retailPrice = $availabilityPricing->getRetail();
        $netPrice = $availabilityPricing->getNet();

        foreach ($promotions as $promotion) {

            $discount = $promotion->getDiscount();
            $rateRetailPrice = (int) round($retailPrice * (1 - $discount / 100));
            $rateNetPrice = (int) round($netPrice * (1 - $discount / 100));

            $rates[] = new Rate(
                $promotion->getName(),
                $rateRetailPrice,
                $rateNetPrice
            );
        }
        $availabilityPricing->setRates($rates);
    }

}