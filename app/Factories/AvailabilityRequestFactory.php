<?php

namespace App\Factories;

use App\Features\Availability\AvailabilityRequest;
use App\Features\Availability\Pricing\MultiDayPricingAvailabilityRequest;
use App\Features\Availability\Pricing\PricingAvailabilityRequest;
use App\Features\Availability\Pricing\SingleDayPricingAvailabilityRequest;
use App\Http\Requests\OctoRequest;
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

    public function get(array $requestParams, string $octoCapabilities, int $minBookingSize = ProductService::MIN_BOOKING_SIZE, int $maxBookingSize = ProductService::MAX_BOOKING_SIZE): BaseAvailabilityRequest
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

        $pricing = $this->isPricingAllowed($octoCapabilities);
        $multiDates = !empty($localDateStart) && !empty($localDateEnd);
        if ($pricing && !$multiDates) {
            return new PricingAvailabilityRequest($tourId, $optionId, $localDate, $units, $currency, $minBookingSize, $maxBookingSize);
        }
        if (!$multiDates) {
            $localDateEnd = '';
            $localDateStart = $localDate;
        }

        $availabilityRequest = new AvailabilityRequest($tourId, $optionId, $localDateStart, $localDateEnd, $pricing);
        if ($this->isContentEnabled($octoCapabilities)) {
            $availabilityRequest->setContentEnabled(true);
        }
        $availabilityRequest->setMinBookingSize($minBookingSize);
        $availabilityRequest->setMaxBookingSize($maxBookingSize);
        $availabilityRequest->setUnits($units);
        return $availabilityRequest;
    }

    protected function isPricingAllowed(string $octoCapabilities): bool
    {
        return !empty($octoCapabilities) && str_contains(OctoRequest::CAPABILITIES_PRICING, strtolower($octoCapabilities));
    }

    protected function isContentEnabled(string $octoCapabilities): bool
    {
        return !empty($octoCapabilities) && str_contains(OctoRequest::CAPABILITIES_CONTENT, strtolower($octoCapabilities));
    }
}