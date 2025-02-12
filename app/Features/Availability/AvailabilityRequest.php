<?php

namespace App\Features\Availability;

use App\Exceptions\InvalidAvailabilityIdException;
use App\Exceptions\InvalidUnitIdException;
use App\Facades\OctoRequestFacade;
use App\Interfaces\BaseAvailabilityRequest;
use App\Services\DateTimeService;
use App\Services\OptionService;
use App\Services\TourCMSService;
use App\Models\Availability\Availability;
use App\Models\Availability\AvailabilityPricing;
use App\Models\Availability\AvailabilityUnitPricing;
use App\Services\XMLService;
use SimpleXMLElement;

class AvailabilityRequest extends BaseAvailabilityRequest
{
    protected string $tourId;
    protected string $optionId;
    protected string $localDateStart;
    protected string $localDateEnd;
    protected array $units;
    protected int $minBookingSize;
    protected int $maxBookingSize;
    protected int $maxUnits;
    protected string $cutoff;
    protected string $currency;
    protected bool $allDay = false;
    protected string $productId;

    public function __construct(string $tourId, string $optionId, string $localDateStart, string $localDateEnd = '', bool $allDay = false)
    {
        $this->tourId = $tourId;
        $this->optionId = $optionId;
        $this->localDateStart = $localDateStart;
        $this->localDateEnd = $localDateEnd;
        $this->allDay = $allDay;
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

    /**
     * Get the value of tourId
     */ 
    public function getTourId()
    {
        return $this->tourId;
    }

    /**
     * Set the value of tourId
     *
     * @return  self
     */ 
    public function setTourId($tourId)
    {
        $this->tourId = $tourId;
        return $this;
    }

    /**
     * Get the value of optionId
     */ 
    public function getOptionId()
    {
        return $this->optionId;
    }

    /**
     * Set the value of optionId
     *
     * @return  self
     */ 
    public function setOptionId($optionId)
    {
        $this->optionId = $optionId;
        return $this;
    }

    /**
     * Get the value of units
     */ 
    public function getUnits()
    {
        return $this->units;
    }

    /**
     * Set the value of units
     *
     * @return  self
     */ 
    public function setUnits($units)
    {
        $this->units = $units;
        return $this;
    }

    /**
     * Get the value of minBookingSize
     */ 
    public function getMinBookingSize()
    {
        return $this->minBookingSize;
    }

    /**
     * Set the value of minBookingSize
     *
     * @return  self
     */ 
    public function setMinBookingSize($minBookingSize)
    {
        $this->minBookingSize = $minBookingSize;
        return $this;
    }

    /**
     * Get the value of maxBookingSize
     */
    public function getMaxBookingSize(): int
    {
        return $this->maxBookingSize;
    }

    /**
     * Set the value of maxBookingSize
     *
     * @return  self
     */
    public function setMaxBookingSize(int $maxBookingSize)
    {
        $this->maxBookingSize = $maxBookingSize;
        return $this;
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

        list($startTimeHours, $startTimeMinutes) = explode(":", $departure->start_time ? (string) $departure->start_time : '00:00');
        list($endTimeHours, $endTimeMinutes) = explode(":", $departure->end_time ? (string) $departure->end_time : '23:59');

        $availability->setId("{$departure->start_date}|{$departure->departure_id}");
        $availability->setDepartureId((int) $departure->departure_id);
        $availability->setLocalDateTimeStart(DateTimeService::getISODateTimeString((string) $departure->start_date, $startTimeHours, $startTimeMinutes));
        $availability->setLocalDateTimeEnd(DateTimeService::getISODateTimeString((string) $departure->end_date, $endTimeHours, $endTimeMinutes));
        $availability->setAllDay(false);
        $availability->setOpeningHoursFrom($departure->start_time ?? '00:00');
        $availability->setOpeningHoursTo($departure->end_time ?? '23:59');

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

    public function getOctoStatusFromTourCMSStatus(string $status): string
    {
        if ($status == self::TCMS_STATUS_OPEN || $status == self::TCMS_STATUS_ASKFIRST) {
            return self::OCTO_STATUS_AVAILABLE;
        }

        return self::OCTO_STATUS_CLOSED;
    }

    /**
     * Get the value of productId
     */ 
    public function getProductId(): string
    {
        return $this->productId;
    }

    /**
     * Set the value of productId
     *
     * @return  self
     */ 
    public function setProductId($productId): self
    {
        $this->productId = $productId;

        return $this;
    }

// PRIVATE FUNCTIONS

    protected function checkSpacesRemaining(\SimpleXMLElement $departure): bool
    {
        $spacesRequired = $this->getMinBookingSize();
        if (!empty($this->getUnits())) {
            $spacesRequired = 0;
            foreach ($this->getUnits() as $unit) {
                $spacesRequired += (int) $unit['quantity'];
            }
        }
        return $departure->spaces_remaining >= $spacesRequired;
    }

    protected function checkMaxUnitsExceeded(\SimpleXMLElement $departure): bool
    {
        $totalRequestedUnits = 0;
        if (!empty($this->units)) {
            foreach ($this->units as $unit) {
                $totalRequestedUnits += (int) $unit['quantity'];
            }
        }
        return $this->maxBookingSize >= $totalRequestedUnits;
    }

    protected function fetchDeparturesFromAPI(TourCMSService $tourCMSService): array
    {
        $mappingQueryString = OptionService::getMappingQueryString($this->optionId);

        $response = $tourCMSService->showTourDepartures($this->tourId,$this->localDateStart, $this->localDateEnd, $mappingQueryString);

        if (!isset($response->tour->dates_and_prices)) {
            return [];
        }
        if (true === OctoRequestFacade::isPricingRequired()) {
            $this->currency = (string)$response->tour->sale_currency;
        }
        $departures = $tourCMSService->getArrayFromXmlNode($response->tour->dates_and_prices, 'departure');

        return $departures;
    }

    protected function getAvailabilitiesFromDepartures(array $departures):array
    {   
        $availabilities = [];
        foreach ($departures as $departure) {

            $availability = new Availability;

            list($startTimeHours, $startTimeMinutes) = explode(":", !empty($departure->start_time) ? (string) $departure->start_time : '00:00');
            list($endTimeHours, $endTimeMinutes) = explode(":", !empty($departure->end_time) ? (string) $departure->end_time : '23:59');

            $availability->setContentEnabled($this->contentEnabled);
            $availability->setId($this->generateAvailabilityIdFromDepartureOrComponentObject($departure));
            $availability->setDepartureId((int) $departure->departure_id);
            $availability->setLocalDateTimeStart(DateTimeService::getISODateTimeString((string) $departure->start_date, $startTimeHours, $startTimeMinutes));
            $availability->setLocalDateTimeEnd(DateTimeService::getISODateTimeString((string) $departure->end_date, $endTimeHours, $endTimeMinutes));
            $availability->setAllDay($this->allDay);
            $availability->setAvailable($this->checkSpacesRemaining($departure) && $this->checkMaxUnitsExceeded($departure));
            $availability->setStatus($this->getOctoStatusFromTourCMSStatus((string) $departure->status));
            $availability->setMaxUnits($this->maxUnits);
            $availability->setUtcCutoffAt($this->cutoff);
            $availability->setOpeningHoursFrom(!empty($departure->start_time) ? (string) $departure->start_time : '00:00');
            $availability->setOpeningHoursTo(!empty($departure->end_time) ? (string) $departure->end_time :'23:59');

            if (true === OctoRequestFacade::isPricingRequired()) {
                $pricing = $this->getPricingForMultipleDays($departure);
                $unitPricing = $this->getUnitPricing($departure);
                $availability->setCurrency($this->currency);
                $availability->setPricing($pricing);
                $availability->setUnitPricing($unitPricing);
            }
            if (true === OctoRequestFacade::isContentRequired()) {
                $supplierNote = !empty($departure->supplier_note) ? (string) $departure->supplier_note : '';
                $availability->setTitle("{$this->tourName} {$supplierNote}");
                $availability->setShortDescription(!empty($departure->note) ? (string) $departure->note : null);
            }
            $availabilities[] = $availability;

        } 

        return $availabilities;
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

    protected function getPricingForMultipleDays(SimpleXMLElement $departure): AvailabilityPricing
    {
        $totalPricing = 0;
        $netPrice = 0;
        $ratesArray = $this->ratesFromShowTourDepartureXML($departure);
        foreach ($this->units as $unit) {
            $rateId = explode('|', $unit['id'])[1];
            if(!array_key_exists($rateId, $ratesArray)) {
                throw new InvalidUnitIdException($unit['id']);
            }
            $totalPricing += (float) $ratesArray[$rateId]->rate_price * $unit['quantity'];
            $netPrice += (float) $ratesArray[$rateId]->net_price * $unit['quantity'];
        }
        $totalPricing = $totalPricing * 100;
        $netPrice = $netPrice * 100;

        return new AvailabilityPricing(
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
    protected function getUnitPricing(SimpleXMLElement $departure): array
    {
        $unitPricings = [];

        $productIdWithoutChannel = explode('|', $this->productId)[0];
        $rates = $this->ratesFromShowTourDepartureXML($departure);
        foreach ($rates as $rateId => $rate) {
            $unitPricings[] = 
                (new AvailabilityUnitPricing)
                    ->setUnitId("{$productIdWithoutChannel}|{$rateId}")
                    ->setRetailPrice($rate->rate_price * 100)
                    ->setNetPrice($rate->net_price * 100)
                    ->setCurrency($this->currency);
        }

        return $unitPricings;
    }
}