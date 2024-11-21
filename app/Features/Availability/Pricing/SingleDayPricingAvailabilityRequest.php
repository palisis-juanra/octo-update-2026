<?php

namespace App\Features\Availability\Pricing;

use App\Facades\JSONLog;
use App\Services\OptionService;
use App\Services\TourCMSService;

class SingleDayPricingAvailabilityRequest extends PricingAvailabilityRequest
{
    protected string $tourId;
    protected string $optionId;
    protected string $date;
    protected array $units;
    protected string $currency;
    

    public function __construct(string $tourId, string $optionId, string $date, array $units, string $currency, int $minBookingSize)
    {
        $this->tourId = $tourId;
        $this->optionId = $optionId;
        $this->date = $date;
        $this->units = $units;
        $this->currency = $currency;
        $this->minBookingSize = $minBookingSize;
    }

    protected function fetchComponentsFromTourCMS(TourCMSService $tourCMSService): array
    {
        $ratesParams = $this->generateRatesParamsFromUnits($this->units);
        $params = "date={$this->date}&{$ratesParams}";
        $mappingQueryString = OptionService::getMappingQueryString($this->optionId);
        $params .= "&{$mappingQueryString}";

        $response = $tourCMSService->checkAvailability($params, $this->tourId);
        JSONLog::info(["message" => "Check tour availability response for {$this->date}", "response" => $response]);

        if (empty($response->available_components)) {
            JSONLog::info("No available components for {$this->date}");
            return [];
        }

        $availableComponents = $tourCMSService->getArrayFromXmlNode($response->available_components, 'component');
        JSONLog::info(["message" => "Available components for {$this->date}", "components" => $availableComponents]);

        return $availableComponents;
    }
    
}