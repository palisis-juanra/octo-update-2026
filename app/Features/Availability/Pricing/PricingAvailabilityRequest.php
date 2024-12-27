<?php

namespace App\Features\Availability\Pricing;

use App\Features\Availability\AvailabilityRequest;
use App\Interfaces\BaseAvailabilityRequest;
use App\Models\Availability\Availability;
use App\Models\Availability\AvailabilityPricing;
use App\Services\DateTimeService;
use App\Services\TourCMSService;

abstract class PricingAvailabilityRequest extends AvailabilityRequest
{
    protected string $currency;
    protected array $units;
    protected int $minBookingSize;
    abstract protected function fetchComponentsFromTourCMS(TourCMSService $tourCMSService);

    public function getAvailabilities(TourCMSService $tourCMSService): array
    {
        $components = $this->fetchComponentsFromTourCMS($tourCMSService);
        if (!empty($this->availabilityIds)) {
            $components = $this->filterByAvailabilityIds($components, $this->availabilityIds);
        }
        $availabilities = $this->getAvailabilitiesFromComponents($components);

        return $availabilities;
    }

    public function getOctoStatusFromTourCMSStatus(string $tourCMSStatus): string
    {
        return BaseAvailabilityRequest::OCTO_STATUS_AVAILABLE;
    }

    public function generateRatesParamsFromUnits(array $units): string
    {
        $params = '';

        if (empty($units)) {
            return "r1={$this->minBookingSize}";
        }

        foreach ($units as $rateData) {
            if (!empty($params)) {
                $params .= "&";
            }
            
            $rateIdSplitted = explode('|', $rateData['id']);
            $tourCMSRate = $rateIdSplitted[1];
            $params .= "{$tourCMSRate}={$rateData['quantity']}";
        }

        return $params;
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

            $availability = new Availability;
            
            $availability->setId($this->generateAvailabilityIdFromDepartureOrComponentObject($component));
            $availability->setDepartureId((int) $component->date_id);
            $availability->setLocalDateTimeStart(DateTimeService::getISODateTimeStringFromTimestamp((string) $component->start_time_utcseconds));
            $availability->setLocalDateTimeEnd(DateTimeService::getISODateTimeStringFromTimestamp((string) $component->end_time_utcseconds));
            $availability->setAllDay(false);
            $availability->setAvailable(true);
            $availability->setStatus(self::OCTO_STATUS_AVAILABLE);
            $availability->setMaxUnits($this->maxUnits);
            $availability->setUtcCutoffAt($this->cutoff);
            $availability->setOpeningHoursFrom($component->start_time ?? '00:00');
            $availability->setOpeningHoursTo($component->end_time ?? '23:59');
            $availability->setCurrency($component->sale_currency);
            $availability->setPricing($pricing);
            
            $availabilities[] = $availability;
        }

        return $availabilities;
    }

} 