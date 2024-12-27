<?php

namespace App\Features\Availability;

use App\Exceptions\InvalidAvailabilityIdException;
use App\Interfaces\BaseAvailabilityRequest;
use App\Services\DateTimeService;
use App\Services\OptionService;
use App\Services\TourCMSService;
use App\Models\Availability\Availability;

class AvailabilityRequest extends BaseAvailabilityRequest
{
    protected string $tourId;
    protected string $optionId;
    protected string $localDateStart;
    protected string $localDateEnd;
    protected array $units;
    protected int $minBookingSize;
    protected int $maxUnits;
    protected string $cutoff;

    public function __construct(string $tourId, string $optionId, string $localDateStart, string $localDateEnd = '')
    {
        $this->tourId = $tourId;
        $this->optionId = $optionId;
        $this->localDateStart = $localDateStart;
        $this->localDateEnd = $localDateEnd;
    }

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
        $availabilities = $this->getAvailabilitiesFromDepartures($departures);

        return $availabilities;
    }

    protected function fetchDeparturesFromAPI(TourCMSService $tourCMSService): array
    {
        $mappingQueryString = OptionService::getMappingQueryString($this->optionId);

        $response = $tourCMSService->showTourDepartures($this->tourId,$this->localDateStart, $this->localDateEnd, $mappingQueryString);

        if (!isset($response->tour->dates_and_prices)) {
            return [];
        }

        $departures = $tourCMSService->getArrayFromXmlNode($response->tour->dates_and_prices, 'departure');

        return $departures;
    }

    protected function getAvailabilitiesFromDepartures(array $departures)
    {   
        $availabilities = [];
        foreach ($departures as $departure) {
            
            $availability = new Availability;

            list($startTimeHours, $startTimeMinutes) = explode(":", $departure->start_time ? (string) $departure->start_time : '00:00');
            list($endTimeHours, $endTimeMinutes) = explode(":", $departure->end_time ? (string) $departure->end_time : '23:59');

            $availability->setId("{$departure->start_date}|{$departure->departure_id}");
            $availability->setDepartureId((int) $departure->departure_id);
            $availability->setLocalDateTimeStart(DateTimeService::getISODateTimeString((string) $departure->start_date, $startTimeHours, $startTimeMinutes));
            $availability->setLocalDateTimeEnd(DateTimeService::getISODateTimeString((string) $departure->end_date, $endTimeHours, $endTimeMinutes));
            $availability->setAllDay(false);
            $availability->setAvailable($this->checkSpacesRemaining($departure));
            $availability->setStatus($this->getOctoStatusFromTourCMSStatus((string) $departure->status));
            $availability->setMaxUnits($this->maxUnits);
            $availability->setUtcCutoffAt($this->cutoff);
            $availability->setOpeningHoursFrom($departure->start_time ?? '00:00');
            $availability->setOpeningHoursTo($departure->end_time ?? '23:59');
            
            $availabilities[] = $availability;

        } 

        return $availabilities;
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
            $id = isset($departure->departure_id) ? $departure->departure_id : $departure->date_id;
            $departureId = "{$departure->start_date}|{$id}";
            if (in_array($departureId, $availabilityIds)) {
                $filteredDepartures[] = $departure;
            }
        }
        return $filteredDepartures;
    }

    public function getLocalDateStart(): string
    {
        return $this->localDateStart;
    }

    public function getLocalDateEnd(): string
    {
        return $this->localDateEnd;
    }

    public function getOctoStatusFromTourCMSStatus(string $status): string
    {
        if ($status == self::TCMS_STATUS_OPEN || $status == self::TCMS_STATUS_ASKFIRST) {
            return self::OCTO_STATUS_AVAILABLE;
        }

        return self::OCTO_STATUS_CLOSED;
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
}