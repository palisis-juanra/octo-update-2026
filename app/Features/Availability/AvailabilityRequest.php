<?php

namespace App\Features\Availability;

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
        $availabilities = $this->getAvailabilitiesFromDepartures($departures);
        $this->saveAvailabilities($availabilities);

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

            $availability->setDepartureId((int) $departure->departure_id);
            $availability->setLocalDateTimeStart(DateTimeService::getISODateTimeString((string) $departure->start_date, $startTimeHours, $startTimeMinutes));
            $availability->setLocalDateTimeEnd(DateTimeService::getISODateTimeString((string) $departure->end_date, $endTimeHours, $endTimeMinutes));
            $availability->setAllDay(false);
            $availability->setAvailable($departure->spaces_remaining > 0);
            $availability->setStatus($this->getOctoStatusFromTourCMSStatus((string) $departure->status));
            $availability->setMaxUnits($this->maxUnits);
            $availability->setUtcCutoffAt($this->cutoff);
            $availability->setOpeningHoursFrom($departure->start_time ?? '00:00');
            $availability->setOpeningHoursTo($departure->end_time ?? '23:59');
            
            $availabilities[] = $availability;

        } 

        return $availabilities;
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
}