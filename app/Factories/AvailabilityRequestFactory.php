<?php

namespace App\Factories;

use App\Facades\OctoRequestFacade;
use App\Features\Availability\AvailabilityRequest;
use App\Features\Availability\Pricing\PricingAvailabilityRequest;
use App\Http\Requests\OctoRequest;
use App\Interfaces\BaseAvailabilityRequest;
use App\Models\Product;
use App\Services\AvailabilityService;
use App\Services\ProductService;
use App\Services\UnitService;

class AvailabilityRequestFactory
{
    public function __construct(public ProductService $productService) {}

    public function get(Product $product, array $requestParams): BaseAvailabilityRequest
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

        $pricing = OctoRequestFacade::isCapabilityActive(OctoRequest::CAPABILITIES_PRICING);
        $multiDates = !empty($localDateStart) && !empty($localDateEnd);
        if ($pricing && !$multiDates) {
            return new PricingAvailabilityRequest($product, $optionId, $localDate, $units, $currency, $product->getMinBookingSize(), $product->getMaxBookingSize(), $product->getAllDay());        
        }

        if (!$multiDates) {
            $localDateEnd = '';
            $localDateStart = $localDate;
        }

        $availabilityRequest = new AvailabilityRequest($product, $optionId, $localDateStart, $localDateEnd);

        if (true === OctoRequestFacade::isCapabilityActive(OctoRequest::CAPABILITIES_CONTENT)) {
            $availabilityRequest->setContentEnabled(true);
        }
        $availabilityRequest->setMinBookingSize($product->getMinBookingSize());
        $availabilityRequest->setMaxBookingSize($product->getMaxBookingSize());
        $availabilityRequest->setUnits($units);
        $availabilityRequest->setAllDay(false);

        return $availabilityRequest;
    }
}