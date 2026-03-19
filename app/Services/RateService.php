<?php

namespace App\Services;

use App\Exceptions\InvalidRateIdException;
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

        /* Availability Pricing */

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

        if (null !== $promotionToApply) {
            $this->applyPromotionDiscount($availability, $promotionToApply);
        }

        /* Availability Unit Pricing */

        $unitPricingArray = $availability->getUnitPricing();
        /* @var AvailabilityUnitPricing[] */
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

        return $availability;
    }

    /**
     * Apply a promotion to availabilities
     * @param TourCMSService $tourCMSService
     * @param Availability[] $availabilities
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

}