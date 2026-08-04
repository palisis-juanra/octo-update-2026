<?php

namespace App\Features\Availability\Pricing;

use App\Facades\JSONLog;
use App\Facades\OctoRequestFacade;
use App\Features\Availability\AvailabilityRequest;
use App\Http\Requests\OctoRequest;
use App\Models\Availability\Availability;
use App\Models\Pricing;
use App\Models\Product;
use App\Models\TourCMS\Promotion;
use App\Services\AvailabilityPromotionService;
use App\Services\OptionService;
use App\Services\TourCMSService;
use App\Services\UnitService;

class PricingAvailabilityRequest extends AvailabilityRequest
{
    protected string $currency;
    protected array $units;

    public function __construct(AvailabilityPromotionService $availabilityPromotionService, Product $product, string $optionId, string $localDateStart, array $units, string $currency, ?Promotion $promotion = null)
    {
        parent::__construct($availabilityPromotionService, $product, $optionId, $localDateStart, $localDateStart, $promotion);
        $this->units = $units;
        $this->currency = $currency;
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

    protected function promotionEnrichmentEnabled(): bool
    {
        return false;
    }

    public function getAvailabilities(TourCMSService $tourCMSService): array
    {
        $availabilities = parent::getAvailabilities($tourCMSService);
        $checkAvailcomponents = $this->fetchComponentsFromTourCMS($tourCMSService);
        JSONLog::info(['checkAvailcomponents' => $checkAvailcomponents]);
        $this->updateAvailabilitiesWithCheckAvailComponents($availabilities, $checkAvailcomponents);

        if (OctoRequestFacade::isCapabilityActive(OctoRequest::CAPABILITIES_BOOKINGCOM_RATES)) {
            foreach ($availabilities as $availability) {
                $this->availabilityPromotionService->enrichAvailabilityWithPromotions($this->product, $availability, $this->promotion);
            }
        }

        return $availabilities;
    }

    public function generateRatesParamsFromUnits(array $units): string
    {
        $params = '';

        if (empty($units)) {
            return "r1={$this->product->getMinBookingSize()}";
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

    /**
     * Update availability pricing with prices coming from check avail endpoint
     * @param Availability[] $availabilities
     * @param array $checkAvailcomponents
     * @return void
     */
    protected function updateAvailabilitiesWithCheckAvailComponents(array $availabilities, array $checkAvailcomponents): void
    {
        $checkAvailComponentsIndexed = $this->indexCheckAvailComponents($checkAvailcomponents);
        foreach ($availabilities as $availability) {

            if (!isset($checkAvailComponentsIndexed[$availability->getId()])) {
                $availability->setAvailable(false);
                continue;
            }

            $totalPricing = (int) round((float) $checkAvailComponentsIndexed[$availability->getId()]->total_price * 100);
            $netPrice = (int) round((float) $checkAvailComponentsIndexed[$availability->getId()]->net_price * 100);
            
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