<?php

namespace App\Models;

use App\Interfaces\AvailabilityRequestInterface;
use App\Services\AvailabilityService;
use App\Services\ProductService;
use App\Services\TourCMSService;
use Illuminate\Support\Facades\Log;

class MultiDayAvailabilityRequest implements AvailabilityRequestInterface
{
    public string $productId;
    public string $optionId;
    public string $localDateStart;
    public string $localDateEnd;

    public function __construct(array $availabilityRequest)
    {
        $this->productId = $availabilityRequest[AvailabilityService::PARAM_PRODUCT_ID]; 
        $this->optionId = $availabilityRequest[AvailabilityService::PARAM_OPTION_ID]; 
        $this->localDateStart = $availabilityRequest[AvailabilityService::PARAM_LOCAL_DATE_START]; 
        $this->localDateEnd = $availabilityRequest[AvailabilityService::PARAM_LOCAL_DATE_END];
    }

    public function getDepartures(TourCMSService $tourCMSService): array
    {
        $tourId = ProductService::getTourIdFromProductId($this->productId);
        $response = $tourCMSService->showTourDepartures($tourId,$this->localDateStart, $this->localDateEnd);
        Log::info($response->asXML());

        if (!isset($response->tour->dates_and_prices)) {
            return [];
        }

        $departures = $tourCMSService->getArrayFromXmlNode($response->tour->dates_and_prices, 'departure');

        return $departures;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function getOptionId(): string
    {
        return $this->optionId;
    }

    public function getLocalDateStart(): string
    {
        return $this->localDateStart;
    }

    public function getLocalDateEnd(): string
    {
        return $this->localDateEnd;
    }
}