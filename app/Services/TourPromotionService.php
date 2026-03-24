<?php

namespace App\Services;

use App\Exceptions\InvalidRateIdException;
use App\Models\TourCMS\Promotion;

/**
 * Service responsible for retrieving and validating tour promotions.
 */
class TourPromotionService
{
    protected TourCMSService $tourCMSService;

    public function __construct(TourCMSService $tourCMSService)
    {
        $this->tourCMSService = $tourCMSService;
    }

    /**
     * Fetch all promotions available for a given tour.
     *
     * The response is retrieved from TourCMS and transformed into an array
     * of Promotion domain objects.
     *
     * @param int $tourId Tour identifier.
     * @return Promotion[] List of promotions available for the given tour.
     */
    public function getTourPromotions(int $tourId): array
    {
        $tourPromotionsXML = $this->tourCMSService->getTourPromotions($tourId);

        if (empty($tourPromotionsXML->promotions)) {
            return [];
        }

        $promotionsData = XMLService::getArrayFromXmlNode($tourPromotionsXML->promotions, 'promotion') ?? [];
        
        $promotions = [];
        $promotions[] = Promotion::createOpenPromotion();
        
        foreach ($promotionsData as $promotionData) {
            if ((string) $promotionData->name == Promotion::OPEN_PROMOTION_NAME) {
                continue;
            }
            $promotions[] = Promotion::fromXML($promotionData);
        }
        return $promotions;
    }

    /**
     * Retrieve a single promotion by name for a given tour.
     *
     * @param int $tourId Tour identifier.
     * @param string $promotionName Promotion name to search for.
     * @return Promotion|null The matching promotion, or null if not found.
     */
    public function getTourPromotionByName(int $tourId, string $promotionName): ?Promotion
    {
        foreach ($this->getTourPromotions($tourId) as $promotion) {
            if ($promotion->getName() === $promotionName) {
                return $promotion;
            }
        }

        return null;
    }

    /**
     * Validate that a given rate id exists as a valid promotion for the tour.
     *
     * An empty rate id is considered valid and no exception is thrown.
     *
     * @param int $tourId Tour identifier.
     * @param string|null $rateId Rate identifier received from the consumer.
     *
     * @throws InvalidRateIdException Thrown when the provided rate id does not exist.
     *
     * @return void
     */
    public function validateRateId(int $tourId, ?string $rateId): void
    {
        if (empty($rateId)) {
            return;
        }

        if ($this->getTourPromotionByName($tourId, $rateId) === null) {
            throw new InvalidRateIdException($rateId);
        }
    }

    /**
     * Fetch all promotions for a tour indexed by promotion name.
     *
     * This is useful when multiple lookups need to be performed in the same flow
     * and we want to avoid repeated linear searches.
     *
     * Example result:
     * [
     *     'SUMMER10' => Promotion,
     *     'VIP20' => Promotion,
     * ]
     *
     * @param int $tourId Tour identifier.
     * @return array<string, Promotion> Promotions indexed by promotion name.
     */
    public function getTourPromotionsIndexedByName(int $tourId): array
    {
        $indexedPromotions = [];

        foreach ($this->getTourPromotions($tourId) as $promotion) {
            $indexedPromotions[$promotion->getName()] = $promotion;
        }

        return $indexedPromotions;
    }
}