<?php

namespace App\Factories;

use App\Facades\OctoRequestFacade;
use App\Features\Availability\AvailabilityRequest;
use App\Features\Availability\Pricing\PricingAvailabilityRequest;
use App\Http\Requests\OctoRequest;
use App\Interfaces\BaseAvailabilityRequest;
use App\Models\Product;
use App\Models\TourCMS\Promotion;
use App\Services\AvailabilityPromotionService;
use App\Services\AvailabilityService;
use App\Services\ProductService;
use App\Services\TourPromotionService;
use App\Services\UnitService;

class AvailabilityRequestFactory
{
    public function __construct(
        public ProductService $productService,
        public TourPromotionService $tourPromotionService,
        public AvailabilityPromotionService $availabilityPromotionService) {}

    public function get(Product $product, array $requestParams): BaseAvailabilityRequest
    {
        $optionId = $requestParams[AvailabilityService::PARAM_OPTION_ID] ?? '';
        $localDate = $requestParams[AvailabilityService::PARAM_LOCAL_DATE] ?? '';
        $localDateStart = $requestParams[AvailabilityService::PARAM_LOCAL_DATE_START] ?? '';
        $localDateEnd = $requestParams[AvailabilityService::PARAM_LOCAL_DATE_END] ?? '';
        $availabilityIds = $requestParams[AvailabilityService::PARAM_AVAILABILITY_IDS] ?? [];
        $currency = $requestParams[AvailabilityService::PARAM_CURRENCY] ?? '';
        $units = $requestParams[UnitService::PARAM_UNITS] ?? [];

        $rateId = $requestParams[OctoRequest::RATE_ID] ?? Promotion::OPEN_PROMOTION_NAME;
        $promotion = Promotion::createOpenPromotion();
        if ($rateId !== Promotion::OPEN_PROMOTION_NAME) {
            $promotion = $this->tourPromotionService->getTourPromotionByName($product->getTourId(), $rateId);
        }

        if (! empty($availabilityIds)) {
            if (count($availabilityIds) == 1) {
                $localDate = explode('|', $availabilityIds[0])[0];
            } else {
                $dates = [];
                foreach ($availabilityIds as $availabilityId) {
                    $dates[] = explode('|', $availabilityId)[0];
                }
                $localDateStart = min($dates);
                $localDateEnd = max($dates);
            }
        }

        $pricing = OctoRequestFacade::isCapabilityActive(OctoRequest::CAPABILITIES_PRICING);
        $multiDates = ! empty($localDateStart) && ! empty($localDateEnd);
        if ($pricing && ! $multiDates) {
            return new PricingAvailabilityRequest($this->availabilityPromotionService, $product, $optionId, $localDate, $units, $currency, $promotion);
        }

        if (! $multiDates) {
            $localDateEnd = '';
            $localDateStart = $localDate;
        }

        $availabilityRequest = new AvailabilityRequest($this->availabilityPromotionService, $product, $optionId, $localDateStart, $localDateEnd, $promotion);

        if (OctoRequestFacade::isCapabilityActive(OctoRequest::CAPABILITIES_CONTENT) === true) {
            $availabilityRequest->setContentEnabled(true);
        }

        $availabilityRequest->setUnits($units);

        return $availabilityRequest;
    }
}
