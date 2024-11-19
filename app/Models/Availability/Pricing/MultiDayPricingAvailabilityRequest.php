<?php

namespace App\Models\Availability\Pricing;

use App\Facades\JSONLog;
use App\Models\Availability\Availability;
use App\Interfaces\BaseAvailabilityRequest;
use App\Services\DateTimeService;
use App\Services\TourCMSService;
use SimpleXMLElement;
use stdClass;

class MultiDayPricingAvailabilityRequest extends PricingAvailabilityRequest
{
    protected string $tourId;
    protected string $optionId;
    protected string $localDateStart;
    protected string $localDateEnd;
    protected string $currency;

    public function __construct(string $tourId, string $optionId, string $localDateStart, string $localDateEnd, string $currency)
    {
        $this->tourId = $tourId;
        $this->optionId = $optionId;
        $this->localDateStart = $localDateStart;
        $this->localDateEnd = $localDateEnd;
        $this->currency = $currency;
    }

    public function getAvailabilities(TourCMSService $tourCMSService): array
    {
        JSONLog::info("Calling TourCMS for availabilities asynchronously for period {$this->localDateStart} - {$this->localDateEnd}");
        return parent::getAvailabilities($tourCMSService);
    }

    protected function fetchComponentsFromTourCMS(TourCMSService $tourCMSService): array
    {
        $components = [];

        $ratesQueryString = "r1=1";
        $responses = $tourCMSService->multiCheckAvail($this->tourId, $this->localDateStart, $this->localDateEnd, $ratesQueryString);
        
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