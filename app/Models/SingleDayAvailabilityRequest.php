<?php

namespace App\Models;

use App\Interfaces\AvailabilityRequestInterface;
use App\Services\AvailabilityService;
use App\Services\TourCMSService;

class SingleDayAvailabilityRequest implements AvailabilityRequestInterface
{
    protected string $productId;
    protected string $optionId;
    protected string $localDateStart;

    public function __construct(array $requestParams)
    {
        $this->productId = $requestParams[AvailabilityService::PARAM_PRODUCT_ID];
        $this->optionId = $requestParams[AvailabilityService::PARAM_OPTION_ID] ?? '';
        $this->localDateStart = $requestParams[AvailabilityService::PARAM_LOCAL_DATE_START];
    }

    public function getDepartures(TourCMSService $tourCMSService): array
    {
        $params = "date={$this->localDateStart}";
        $response = $tourCMSService->checkAvailability($params, $this->productId);
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
}