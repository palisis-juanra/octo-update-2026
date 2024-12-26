<?php

namespace App\Factories;

use App\Features\Availability\AvailabilityRequest;
use App\Features\Availability\Pricing\MultiDayPricingAvailabilityRequest;
use App\Features\Availability\Pricing\SingleDayPricingAvailabilityRequest;
use App\Interfaces\BaseAvailabilityRequest;
use App\Services\AvailabilityService;
use App\Services\ProductService;

class AvailabilityRequestFactory
{
    public ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function get(array $requestParams, string $octoCapabilities, int $minBookingSize = 1): BaseAvailabilityRequest
    {
        $productId = $requestParams[AvailabilityService::PARAM_PRODUCT_ID] ?? '';
        $tourId = $this->productService->getTourIdFromProductId($productId) ?? '';
        $optionId = $requestParams[AvailabilityService::PARAM_OPTION_ID] ?? '';
        $localDate = $requestParams[AvailabilityService::PARAM_LOCAL_DATE] ?? '';
        $localDateStart = $requestParams[AvailabilityService::PARAM_LOCAL_DATE_START] ?? '';
        $localDateEnd = $requestParams[AvailabilityService::PARAM_LOCAL_DATE_END] ?? '';
        $availabilityIds = $requestParams[AvailabilityService::PARAM_AVAILABILITY_IDS] ?? [];
        $currency = $requestParams[AvailabilityService::PARAM_CURRENCY] ?? '';
        $units = $requestParams[AvailabilityService::PARAM_UNITS] ?? [];

        if (!empty($availabilityIds)) {
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

        if (!empty($octoCapabilities) && strtolower($octoCapabilities) === 'pricing') {
            
            if (!empty($localDateStart) && !empty($localDateEnd)) {
                return new MultiDayPricingAvailabilityRequest($tourId, $optionId, $localDateStart, $localDateEnd, $units, $currency, $minBookingSize);
            }

            return new SingleDayPricingAvailabilityRequest($tourId, $optionId, $localDate, $units, $currency, $minBookingSize);
        }
        
        if (!empty($localDateStart) && !empty($localDateEnd)) {
            $availabilityRequest = new AvailabilityRequest($tourId, $optionId, $localDateStart, $localDateEnd);
            $availabilityRequest->setMinBookingSize($minBookingSize);
            $availabilityRequest->setUnits($units);
            return $availabilityRequest;
        }

        $availabilityRequest = new AvailabilityRequest($tourId, $optionId, $localDate);
        $availabilityRequest->setMinBookingSize($minBookingSize);
        $availabilityRequest->setUnits($units);
        return $availabilityRequest;
    }
}