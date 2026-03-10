<?php

namespace App\Services;

use App\Exceptions\InvalidRateIdException;
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
}