<?php

namespace App\Factories;

use App\Features\Availability\AvailabilityRequest;
use App\Features\Availability\Pricing\MultiDayPricingAvailabilityRequest;
use App\Features\Availability\Pricing\SingleDayPricingAvailabilityRequest;
use App\Interfaces\BaseAvailabilityRequest;
use App\Services\AvailabilityService;
use App\Services\ProductService;
use App\Services\UnitService;

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
        $units = $requestParams[UnitService::PARAM_UNITS] ?? [];

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

        $pricing = !empty($octoCapabilities) && strtolower($octoCapabilities) === 'pricing';
        $multiDates = !empty($localDateStart) && !empty($localDateEnd);
        if ($pricing && !$multiDates) {
            return new SingleDayPricingAvailabilityRequest($tourId, $optionId, $localDate, $units, $currency, $minBookingSize);
        }
        if (!$multiDates) {
            $localDateEnd = '';
            $localDateStart = $localDate;
        }

        $availabilityRequest = new AvailabilityRequest($tourId, $optionId, $localDateStart, $localDateEnd, $pricing);
        $availabilityRequest->setMinBookingSize($minBookingSize);
        $availabilityRequest->setUnits($units);
        return $availabilityRequest;
    }
}