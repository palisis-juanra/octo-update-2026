<?php

namespace App\Features\Availability\Pricing;

use App\Features\Availability\AvailabilityRequest;
use App\Interfaces\BaseAvailabilityRequest;
use App\Models\Availability\Availability;
use App\Models\Availability\AvailabilityPricing;
use App\Models\Product;
use App\Services\DateTimeService;
use App\Services\OptionService;
use App\Services\TourCMSService;

class PricingAvailabilityRequest extends AvailabilityRequest
{
    protected string $currency;
    protected array $units;
    protected int $minBookingSize;
    protected int $maxBookingSize;
    protected string $tourId;
    protected string $optionId;
    protected string $localDateStart;
    protected string $localDateEnd;
    protected bool $allowPricing = true;

    public function __construct(string $tourId, string $optionId, string $localDateStart, array $units, string $currency, int $minBookingSize, int $maxBookingSize)
    {
        $this->tourId = $tourId;
        $this->optionId = $optionId;
        $this->localDateStart = $localDateStart;
        $this->localDateEnd = $localDateStart;
        $this->units = $units;
        $this->currency = $currency;
        $this->minBookingSize = $minBookingSize;
        $this->maxBookingSize = $maxBookingSize;

    }
    public function getAvailabilities(TourCMSService $tourCMSService): array
    {
        $availabilities = parent::getAvailabilities($tourCMSService);
        $checkAvailcomponents = $this->fetchComponentsFromTourCMS($tourCMSService);
        $this->validateAvailableComponents($availabilities, $checkAvailcomponents);
        return $availabilities;
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

    protected function validateAvailableComponents(array $components, array $checkAvailcomponents): void
    {
        $checkAvailcomponentsIndexed = array_combine(
            array_map(function($checkAvailcomponent) {
                return $this->generateAvailabilityIdFromDepartureOrComponentObject($checkAvailcomponent);
            }, $checkAvailcomponents),
            array_map(function($checkAvailcomponent) {
                return $checkAvailcomponent;
            }, $checkAvailcomponents)
        );

        foreach ($components as $component) {
            if (!isset($checkAvailcomponentsIndexed[$component->getId()])) {
                $component->setAvailable(false);
            } else {
                $totalPricing = $checkAvailcomponentsIndexed[$component->getId()]->total_price * 100;
                $netPrice = $checkAvailcomponentsIndexed[$component->getId()]->net_price * 100;
                
                $pricing = new AvailabilityPricing(
                    $totalPricing,
                    $totalPricing,
                    $netPrice,
                    $this->currency
                );
                $component->setPricing($pricing);
            }
        }
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