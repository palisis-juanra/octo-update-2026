<?php

namespace App\Features\Availability\Pricing;

use App\Facades\JSONLog;
use App\Features\Availability\AvailabilityRequest;
use App\Interfaces\BaseAvailabilityRequest;
use App\Models\Availability\Availability;
use App\Models\Pricing;
use App\Models\Product;
use App\Services\DateTimeService;
use App\Services\OptionService;
use App\Services\TourCMSService;
use App\Services\UnitService;

class PricingAvailabilityRequest extends AvailabilityRequest
{
    protected string $currency;
    protected array $units;
    protected int $minBookingSize;
    protected int $maxBookingSize;
    protected Product $product;
    protected string $optionId;
    protected string $localDateStart;
    protected string $localDateEnd;
    protected bool $allowPricing = true;
    protected bool $allDay = false;

    public function __construct(Product $product, string $optionId, string $localDateStart, array $units, string $currency, int $minBookingSize, int $maxBookingSize, bool $allDay = false)
    {
        $this->product = $product;
        $this->optionId = $optionId;
        $this->localDateStart = $localDateStart;
        $this->localDateEnd = $localDateStart;
        $this->units = $units;
        $this->currency = $currency;
        $this->minBookingSize = $minBookingSize;
        $this->maxBookingSize = $maxBookingSize;
        $this->allDay = $allDay;
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

    public function getAvailabilities(TourCMSService $tourCMSService): array
    {
        $availabilities = parent::getAvailabilities($tourCMSService);
        $checkAvailcomponents = $this->fetchComponentsFromTourCMS($tourCMSService);
        JSONLog::info(['checkAvailcomponents' => $checkAvailcomponents]);
        $this->updateAvailabilitiesWithCheckAvailComponents($availabilities, $checkAvailcomponents);
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
            
            $tourCMSRate = UnitService::getTourCMSRateId($rateData['id']);
            $params .= "{$tourCMSRate}={$rateData['quantity']}";
        }

        return $params;
    }

    public function indexCheckAvailComponents(array $checkAvailcomponents): array
    {
        $checkAvailcomponentsIndexed = [];
        foreach ($checkAvailcomponents as $checkAvailcomponent) {
            $checkAvailcomponentsIndexed[$this->generateAvailabilityIdFromDepartureOrComponentObject($checkAvailcomponent)] = $checkAvailcomponent;
        }
        return $checkAvailcomponentsIndexed;
    }

    protected function getAvailabilitiesFromComponents(array $components): array
    {   
        $availabilities = [];

        foreach ($components as $component) {

            $totalPricing = $component->total_price * 100;
            $netPrice = $component->net_price * 100;

            $pricing = new Pricing(
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

    /**
     * Summary of validateAvailableComponents
     * @param Availability[] $availabilities
     * @param array $checkAvailcomponents
     * @return void
     */
    protected function updateAvailabilitiesWithCheckAvailComponents(array $availabilities, array $checkAvailcomponents): void
    {
        $checkAvailcomponentsIndexed = $this->indexCheckAvailComponents($checkAvailcomponents);
        foreach ($availabilities as $availability) {

            if (!isset($checkAvailcomponentsIndexed[$availability->getId()])) {
                $availability->setAvailable(false);
                continue;
            }

            $totalPricing = $checkAvailcomponentsIndexed[$availability->getId()]->total_price * 100;
            $netPrice = $checkAvailcomponentsIndexed[$availability->getId()]->net_price * 100;
            
            $pricing = new Pricing(
                $totalPricing,
                $totalPricing,
                $netPrice,
                $this->currency
            );
            $availability->setPricing($pricing);
            
        }
    }

    protected function fetchComponentsFromTourCMS(TourCMSService $tourCMSService): array
    {
        $ratesParams = $this->generateRatesParamsFromUnits($this->units);
        $params = "date={$this->localDateStart}&{$ratesParams}";
        $mappingQueryString = OptionService::getMappingQueryString($this->optionId);
        $params .= "&{$mappingQueryString}";
        $response = $tourCMSService->checkAvailability($params, $this->product->getTourId());
        if (empty($response->available_components)) {
            return [];
        }
        $availableComponents = $tourCMSService->getArrayFromXmlNode($response->available_components, 'component');
        return $availableComponents;
    }
} 