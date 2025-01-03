<?php

namespace App\Features\Availability\Pricing;

use App\Facades\JSONLog;
use App\Services\OptionService;
use App\Services\TourCMSService;

class SingleDayPricingAvailabilityRequest extends PricingAvailabilityRequest
{
    protected string $tourId;
    protected string $optionId;
    protected string $localDateStart;
    protected array $units;
    protected string $currency;
    

    public function __construct(string $tourId, string $optionId, string $localDateStart, array $units, string $currency, int $minBookingSize)
    {
        $this->tourId = $tourId;
        $this->optionId = $optionId;
        $this->localDateStart = $localDateStart;
        $this->units = $units;
        $this->currency = $currency;
        $this->minBookingSize = $minBookingSize;
    }

    protected function fetchComponentsFromTourCMS(TourCMSService $tourCMSService): array
    {
        $ratesParams = $this->generateRatesParamsFromUnits($this->units);
        $params = "date={$this->localDateStart}&{$ratesParams}";
        $mappingQueryString = OptionService::getMappingQueryString($this->optionId);
        $params .= "&{$mappingQueryString}";

        $response = $tourCMSService->checkAvailability($params, $this->tourId);
        if (empty($response->available_components)) {
            return [];
        }
        $availableComponents = $tourCMSService->getArrayFromXmlNode($response->available_components, 'component');
        return $availableComponents;
    }
    

    /**
     * Get the value of localDateStart
     */ 
    public function getLocalDateStart(): string
    {
        return $this->localDateStart;
    }

    /**
     * Set the value of localDateStart
     *
     * @return  self
     */ 
    public function setLocalDateStart($localDateStart)
    {
        $this->localDateStart = $localDateStart;

        return $this;
    }
}