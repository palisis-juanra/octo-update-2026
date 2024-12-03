<?php

namespace App\Features\Availability\Pricing;

use App\Facades\JSONLog;
use App\Services\OptionService;
use App\Services\TourCMSService;
use SimpleXMLElement;

class MultiDayPricingAvailabilityRequest extends PricingAvailabilityRequest
{
    protected string $tourId;
    protected string $optionId;
    protected string $localDateStart;
    protected string $localDateEnd;
    protected array $units;
    protected string $currency;
    protected int $minBookingSize;
    

    public function __construct(string $tourId, string $optionId, string $localDateStart, string $localDateEnd, array $units, string $currency, int $minBookingSize = 1)
    {
        $this->tourId = $tourId;
        $this->optionId = $optionId;
        $this->localDateStart = $localDateStart;
        $this->localDateEnd = $localDateEnd;
        $this->units = $units;
        $this->currency = $currency;
        $this->minBookingSize = $minBookingSize;
    }

    public function getAvailabilities(TourCMSService $tourCMSService): array
    {
        JSONLog::info("Calling TourCMS for availabilities asynchronously for period {$this->localDateStart} - {$this->localDateEnd}");
        return parent::getAvailabilities($tourCMSService);
    }

    protected function fetchComponentsFromTourCMS(TourCMSService $tourCMSService): array
    {
        $components = [];

        $ratesParams = $this->generateRatesParamsFromUnits($this->units);
        $mappingQueryString = OptionService::getMappingQueryString($this->optionId);
        $queryString = "{$ratesParams}&{$mappingQueryString}";

        $responses = $tourCMSService->multiCheckAvail($this->tourId, $this->localDateStart, $this->localDateEnd, $queryString);
        
        foreach ($responses as $date => $responseObject) {

            $response = new SimpleXMLElement($responseObject->response);
            JSONLog::info(["message" => "Check tour availability response for {$date}", "response" => $response]);
            if (empty($response->available_components)) {
                JSONLog::info("No available components for {$date}");
                continue;
            }
            $availableComponents = $tourCMSService->getArrayFromXmlNode($response->available_components, 'component');
            JSONLog::info(["message" => "Available components for {$date}", "components" => $availableComponents]);
            $components = array_merge($components, $availableComponents);
        }

        return $components;
    }
}