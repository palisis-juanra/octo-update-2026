<?php

namespace App\Models\Availability\Pricing;

use App\Interfaces\BaseAvailabilityRequest;
use App\Models\Availability\Availability;
use App\Services\DateTimeService;
use App\Services\TourCMSService;

abstract class PricingAvailabilityRequest extends BaseAvailabilityRequest
{
    protected string $currency;
    abstract protected function fetchComponentsFromTourCMS(TourCMSService $tourCMSService);

    public function getAvailabilities(TourCMSService $tourCMSService): array
    {
        $components = $this->fetchComponentsFromTourCMS($tourCMSService);
        $availabilities = $this->getAvailabilitiesFromComponents($components);

        return $availabilities;
    }

    protected function getAvailabilitiesFromComponents(array $components)
    {   
        $availabilities = [];

        foreach ($components as $component) {

            $totalPricing = $component->total_price * 100;
            $netPrice = $component->net_price * 100;

            $pricing = new AvailabilityPricing(
                $totalPricing,
                $totalPricing,
                $netPrice,
                $this->currency
            );


            $availabilities[] = new Availability(
                (string) $component->date_id,
                DateTimeService::getISODateTimeStringFromTimestamp(timestamp: (string) $component->start_time_utcseconds),
                DateTimeService::getISODateTimeStringFromTimestamp((string) $component->end_time_utcseconds),
                false,
                true,
                self::OCTO_STATUS_AVAILABLE,
                null,
                null,
                $this->maxUnits,
                $this->cutoff,
                $component->start_time ?? '00:00',
                $component->end_time ?? '23:59',
                (string) $component->sale_currency,
                $pricing
            );
        }

        return $availabilities;
    }

    public function getOctoStatusFromTourCMSStatus(string $tourCMSStatus): string
    {
        return BaseAvailabilityRequest::OCTO_STATUS_AVAILABLE;
    }

    public function generateRatesParams(array $rates): string
    {
        $params = '';
        foreach ($rates as $rateData) {
            if (!empty($params)) {
                $params .= "&";
            }
            $params .= "{$rateData['id']}={$rateData['quantity']}";
        }

        return $params;
    }
} 