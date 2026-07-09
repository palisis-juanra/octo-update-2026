<?php

namespace App\Features\Availability;

use App\Exceptions\InvalidAvailabilityIdException;
use App\Exceptions\InvalidUnitIdException;
use App\Exceptions\NoMatchingDataException;
use App\Facades\JsonLog;
use App\Facades\OctoRequestFacade;
use App\Http\Requests\OctoRequest;
use App\Interfaces\BaseAvailabilityRequest;
use App\Services\DateTimeService;
use App\Services\OptionService;
use App\Services\TourCMSService;
use App\Models\Availability\Availability;
use App\Models\Availability\AvailabilityUnitPricing;
use App\Models\Pricing;
use App\Models\Product;
use App\Models\TourCMS\Promotion;
use App\Services\AvailabilityPromotionService;
use App\Services\CutoffService;
use App\Services\UnitService;
use App\Services\XMLService;
use SimpleXMLElement;

class AvailabilityRequest extends BaseAvailabilityRequest
{
    protected AvailabilityPromotionService $availabilityPromotionService;
    protected Product $product;
    protected string $tourId;
    protected string $optionId;
    protected string $localDateStart;
    protected string $localDateEnd;
    protected array $units;
    protected string $currency;
    protected bool $allDay = false;
    protected ?Promotion $promotion = null;

    public function __construct(AvailabilityPromotionService $availabilityPromotionService, Product $product, string $optionId, string $localDateStart, string $localDateEnd = '', ?Promotion $promotion = null)
    {
        $this->availabilityPromotionService = $availabilityPromotionService;
        $this->product = $product;
        $this->tourId = $product->getTourId();
        $this->optionId = $optionId;
        $this->localDateStart = $localDateStart;
        $this->localDateEnd = $localDateEnd;
        $this->allDay = $product->getAllDay();
        $this->promotion = $promotion;
    }

// GET SET FUNCTIONS

    public function getLocalDateStart(): string
    {
        return $this->localDateStart;
    }

    public function getLocalDateEnd(): string
    {
        return $this->localDateEnd;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getOptionId()
    {
        return $this->optionId;
    }

    public function setOptionId(string $optionId)
    {
        $this->optionId = $optionId;
        return $this;
    }

    public function getUnits()
    {
        return $this->units;
    }

    public function setUnits(array $units)
    {
        $this->units = $units;
        return $this;
    }

    public function getMinBookingSize(): int
    {
        return $this->product->getMinBookingSize();
    }

    public function getMaxBookingSize(): int
    {
        return $this->product->getMaxBookingSize();
    }

    // PUBLIC FUNCTIONS

    /**
     * Fetch departures info from API and return the availabilities
     * 
     * @return Availability[]
     */
    public function getAvailabilities(TourCMSService $tourCMSService): array
    {
        $departures = $this->fetchDeparturesFromAPI($tourCMSService);
        if (!empty($this->availabilityIds)) {
            $departures = $this->filterByAvailabilityIds($departures, $this->availabilityIds);
        }
        return $this->getAvailabilitiesFromDepartures($departures);
    }

    public function getAvailabilityFromDeparturesById(string $availabilityId, array $departures): Availability
    {   
        $filterResult = $this->filterByAvailabilityIds($departures, [$availabilityId]); 
        if (empty($filterResult)) {
            throw new InvalidAvailabilityIdException($availabilityId);
        }

        $departure = $filterResult[0];  
        $availability = new Availability;

        list($startTimeHours, $startTimeMinutes) = explode(":", isset($departure->start_time) && DateTimeService::validateTime((string) $departure->start_time) ? (string) $departure->start_time : '00:00');
        list($endTimeHours, $endTimeMinutes) = explode(":", isset($departure->end_time) && DateTimeService::validateTime((string) $departure->end_time) ? (string) $departure->end_time : '23:59');

        $availability->setId("{$departure->start_date}|{$departure->departure_id}");
        $availability->setDepartureId((int) $departure->departure_id);
        $availability->setLocalDateTimeStart(DateTimeService::getISODateTimeString((string) $departure->start_date, $startTimeHours, $startTimeMinutes));
        $availability->setLocalDateTimeEnd(DateTimeService::getISODateTimeString((string) $departure->end_date, $endTimeHours, $endTimeMinutes));
        $availability->setAllDay(false);
        $availability->setOpeningHoursFrom(isset($departure->start_time) && DateTimeService::validateTime((string) $departure->start_time) ? (string) $departure->start_time : '00:00');
        $availability->setOpeningHoursTo(isset($departure->end_time) && DateTimeService::validateTime((string) $departure->end_time) ? (string) $departure->end_time : '23:59');

        return $availability;
    }

    public function filterByAvailabilityIds(array $departures, array $availabilityIds): array
    {
        $filteredDepartures = [];
        foreach ($departures as $departure) {
            if (in_array($this->generateAvailabilityIdFromDepartureOrComponentObject($departure), $availabilityIds)) {
                $filteredDepartures[] = $departure;
            }
        }
        return $filteredDepartures;
    }

    public function getOctoStatus(string $tcmsStatus, bool $available): string
    {
        if ($tcmsStatus == self::TCMS_STATUS_OPEN || $tcmsStatus == self::TCMS_STATUS_ASKFIRST) {

            return $available ? self::OCTO_STATUS_AVAILABLE : self::OCTO_STATUS_SOLD_OUT;
        }

        return self::OCTO_STATUS_CLOSED;
    }

// PRIVATE FUNCTIONS

    protected function howManySpacesAreRequested(): int
    {
        if (empty($this->getUnits())) {
            return $this->getMinBookingSize();
        }

        $spacesRequired = 0;
        foreach ($this->getUnits() as $unit) {
            $spacesRequired += (int) $unit['quantity'];
        }
        return $spacesRequired;
    }

    protected function areSufficientSpacesInDeparture(SimpleXMLElement $departure): bool
    {
        return (int) $departure->spaces_remaining >= $this->howManySpacesAreRequested();
    }

    protected function checkMaxUnitsExceeded(): bool
    {
        return $this->getMaxBookingSize() >= $this->howManySpacesAreRequested();
    }

    protected function fetchDeparturesFromAPI(TourCMSService $tourCMSService): array
    {
        $needsPricing = OctoRequestFacade::isCapabilityActive(OctoRequest::CAPABILITIES_PRICING);
        $mappingQueryString = OptionService::getMappingQueryString($this->optionId);
        if (!$needsPricing) {
            $mappingQueryString .= '&hide_prices=1';
        }
        try {

            $response = $tourCMSService->showTourDepartures($this->product->getTourId(),$this->localDateStart, $this->localDateEnd, $mappingQueryString);
            if (!isset($response->tour->dates_and_prices)) {
                return [];
            }
            if ($needsPricing) {
                $this->currency = (string)$response->tour->sale_currency;
            }
            $departures = $tourCMSService->getArrayFromXmlNode($response->tour->dates_and_prices, 'departure');
            $page = 2;
            while (count($departures) < $response->tour->dates_and_prices->total_departure_count) {
                $pagedMappingQueryString = $mappingQueryString . "&page=$page";
                $response = $tourCMSService->showTourDepartures($this->product->getTourId(),$this->localDateStart, $this->localDateEnd, $pagedMappingQueryString);
                $departures = array_merge($departures, $tourCMSService->getArrayFromXmlNode($response->tour->dates_and_prices, 'departure'));
                $page++;
            }
        } catch (NoMatchingDataException $e) {
            JsonLog::error("NO MATCHING DATA error for Show Tour Departures Call");
            throw $e;
        }
        return $departures;
    }

    protected function getAvailabilitiesFromDepartures(array $departures):array
    {
        $availabilities = [];
        foreach ($departures as $departure) {

            $availability = new Availability;

            list($startTimeHours, $startTimeMinutes) = explode(":", isset($departure->start_time) && DateTimeService::validateTime((string) $departure->start_time) ? (string) $departure->start_time : '00:00');
            list($endTimeHours, $endTimeMinutes) = explode(":", isset($departure->end_time) && DateTimeService::validateTime((string) $departure->end_time) ? (string) $departure->end_time : '23:59');

            $availability->setContentEnabled($this->contentEnabled);
            $availability->setId($this->generateAvailabilityIdFromDepartureOrComponentObject($departure));
            $availability->setDepartureId((int) $departure->departure_id);
            $availability->setLocalDateTimeStart(DateTimeService::getISODateTimeString((string) $departure->start_date, $startTimeHours, $startTimeMinutes, $this->product->getTimeZone()));
            $availability->setLocalDateTimeEnd(DateTimeService::getISODateTimeString((string) $departure->end_date, $endTimeHours, $endTimeMinutes, $this->product->getTimeZone()));
            $availability->setAllDay($this->allDay);
            $available = $this->isDepartureAvailable($departure);
            $availability->setAvailable($available);
            $availability->setStatus($this->getOctoStatus((string) $departure->status, $available));
            $availability->setMaxUnits($this->product->getMaxBookingSize());

            $departureCutoff = CutoffService::calculateCutoffForDeparture($this->product, $departure);
            $availability->setUtcCutoffAt($departureCutoff);

            $availability->setOpeningHoursFrom(isset($departure->start_time) && DateTimeService::validateTime((string) $departure->start_time) ? (string) $departure->start_time : '00:00');
            $availability->setOpeningHoursTo(isset($departure->end_time) && DateTimeService::validateTime((string) $departure->end_time) ? (string) $departure->end_time :'23:59');
            $availability->setVacancies((int) $departure->spaces_remaining);
            $availability->setCapacity((int) $departure->spaces_total);

            if (true === OctoRequestFacade::isCapabilityActive(OctoRequest::CAPABILITIES_PRICING)) {
                $pricing = $this->getPricingForShowTourDeparture($departure);
                $unitPricing = $this->getUnitPricingFromShowTourDeparture($departure);
                $availability->setCurrency($this->currency);
                $availability->setPricing($pricing);
                $availability->setUnitPricing($unitPricing);
            }

            if (OctoRequestFacade::isCapabilityActive(OctoRequest::CAPABILITIES_BOOKINGCOM_RATES)) {
                $this->availabilityPromotionService->enrichAvailabilityWithPromotions($this->product, $availability, $this->promotion);
            }

            if (true === OctoRequestFacade::isCapabilityActive(OctoRequest::CAPABILITIES_CONTENT)) {
                $supplierNote = !empty($departure->supplier_note) ? (string) $departure->supplier_note : '';
                $availability->setTitle("{$this->tourName} {$supplierNote}");
                $availability->setShortDescription(!empty($departure->note) ? (string) $departure->note : null);
            }
            $availabilities[] = $availability;

        } 

        return $availabilities;
    }

    protected function isDepartureAvailable(SimpleXMLElement $departure): bool
    {
        return 
            (string) $departure->status === self::TCMS_STATUS_OPEN &&
            $this->areSufficientSpacesInDeparture($departure) && 
            $this->checkMaxUnitsExceeded();
    }

    protected function generateAvailabilityIdFromDepartureOrComponentObject($departure):string
    {
        // Depending if availability or show_tour_departures the departure id changes the var name.
        $departureId = isset($departure->departure_id) ? (string)$departure->departure_id : (string)$departure->date_id;
        $startDate = (string)$departure->start_date;
        return "{$startDate}|{$departureId}";
    }

    protected function ratesFromShowTourDepartureXML(SimpleXMLElement $departure): array
    {
        $ratesArray = [];
        $ratesArray['r1'] = $departure->main_price;
        if (!isset($departure->extra_rates)) {
            return $ratesArray;
        }
        $departureRates = XMLService::getArrayFromXmlNode($departure->extra_rates, 'rate');
        foreach ($departureRates as $rate) {
            $ratesArray[(string)$rate->rate_id] = $rate;
        } 
        return $ratesArray;
    }

        /**
     * Summary of saveAvailabilities
     * @param Availability[]
     * @return void
     */
    protected function saveAvailabilities(array $availabilities): void
    {
        foreach ($availabilities as $availability) {
            $availability->save();
        }
    }

    protected function getPricingForShowTourDeparture(SimpleXMLElement $departure): Pricing
    {
        $totalPricing = 0;
        $netPrice = 0;
        $ratesArray = $this->ratesFromShowTourDepartureXML($departure);

        // Volume pricing
        if ($this->product->getPricingType() === Product::PRICING_TYPE_VOLUME) {
            
            $rateNumber = !empty($this->units) ? (int) $this->units[0]['quantity'] : $this->getMinBookingSize();
            $rateId = "r{$rateNumber}";
            $rate = isset($ratesArray[$rateId]) ? $ratesArray[$rateId] : $this->getMaxRateForVolumeProduct($departure);

            $quantity = !empty($this->units) ? $this->units[0]['quantity'] : $this->getMinBookingSize();

            return new Pricing(
                $rate->rate_price * $quantity * 100,
                $rate->rate_price * $quantity * 100,
                isset($rate->net_price) ? ($rate->net_price * $quantity * 100) : null,
                $this->currency
            );

        }

        // Multiple rates
        foreach ($this->units as $unit) {
            $rateId = UnitService::getTourCMSRateId($unit['id']);
            if(!array_key_exists($rateId, $ratesArray)) {
                throw new InvalidUnitIdException($unit['id']);
            }
            $rateData = $ratesArray[$rateId];

            $rateQuantity = (int) $unit['quantity'];
            $ratePrice = (int) round($rateData->rate_price * 100);

            $rateNetPrice = $ratePrice;
            if (!empty($rateData->net_price)) {
                $rateNetPrice = (int) round($rateData->net_price * 100);
            }

            $totalPricing += $ratePrice * $rateQuantity;
            $netPrice += $rateNetPrice * $rateQuantity;
        }

        return new Pricing(
            $totalPricing,
            $totalPricing,
            $netPrice,
            $this->currency
        );
    }

    /**
     * Build an array of UnitPricing objects
     * @param \SimpleXMLElement $departure
     * @return AvailabilityUnitPricing[]
     */
    protected function getUnitPricingFromShowTourDeparture(SimpleXMLElement $departure): array
    {
        $unitPricings = [];

        $rates = $this->ratesFromShowTourDepartureXML($departure);

        if ($this->product->getPricingType() === Product::PRICING_TYPE_VOLUME) {
            
            $unitId = !empty($this->units) ? $this->units[0]['id'] : "{$this->product->getId()}|r1";

            $rateNumber = !empty($this->units) ? (int) $this->units[0]['quantity'] : $this->getMinBookingSize();
            if ($rateNumber <= $this->getMinBookingSize()) {
                $rateNumber = 1;
            }
            $rateId = "r{$rateNumber}";
            $rate = isset($rates[$rateId]) ? $rates[$rateId] : $this->getMaxRateForVolumeProduct($departure);

            $unitPricings[] = 
                (new AvailabilityUnitPricing)
                    ->setUnitId($unitId)
                    ->setOriginalPrice($rate->rate_price * 100)
                    ->setRetailPrice($rate->rate_price * 100)
                    ->setNetPrice((!empty($rate->net_price) ? $rate->net_price : $rate->rate_price) * 100)
                    ->setCurrency($this->currency);
           
            return $unitPricings;
        }

        foreach ($rates as $rateId => $rate) {
            $unitPricings[] = 
                (new AvailabilityUnitPricing)
                    ->setUnitId("{$this->product->getId()}|{$rateId}")
                    ->setOriginalPrice($rate->rate_price * 100)
                    ->setRetailPrice($rate->rate_price * 100)
                    ->setNetPrice((!empty($rate->net_price) ? $rate->net_price : $rate->rate_price) * 100)
                    ->setCurrency($this->currency);
        }

        return $unitPricings;
    }

    protected function getMaxRateForVolumeProduct(SimpleXMLElement $departure): SimpleXMLElement
    {
        if (!isset($departure->extra_rates)) {
            return $departure->main_price;
        }

        $rates = XMLService::getArrayFromXmlNode($departure->extra_rates, 'rate');
        return array_pop($rates);
    }
}