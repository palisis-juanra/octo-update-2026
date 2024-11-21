<?php

namespace App\Features\Availability;

use App\Interfaces\BaseAvailabilityRequest;
use App\Services\DateTimeService;
use App\Services\OptionService;
use App\Services\TourCMSService;
use App\Models\Availability\Availability;

class AvailabilityRequest extends BaseAvailabilityRequest
{
    public string $tourId;
    public string $optionId;
    public string $localDateStart;
    public string $localDateEnd;
    public int $maxUnits;
    public string $cutoff;

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
            
            $availability->setDepartureId((int) $departure->departure_id);
            $availability->setLocalDateTimeStart(DateTimeService::getISODateTimeString((string) $departure->start_date, '00', '00'));
            $availability->setLocalDateTimeEnd(DateTimeService::getISODateTimeString((string) $departure->end_date, '23', '59'));
            $availability->setAllDay(false);
            //TODO: check if spaces > rates count
            $availability->setAvailable($departure->spaces_remaining > 0);
            $availability->setStatus($this->getOctoStatusFromTourCMSStatus((string) $departure->status));
            $availability->setMaxUnits($this->maxUnits);
            $availability->setUtcCutoffAt($this->cutoff);
            $availability->setOpeningHoursFrom($component->start_time ?? '00:00');
            $availability->setOpeningHoursTo($component->end_time ?? '23:59');

            $availability->save();
            
            $availabilities[] = $availability;

        } 

        return $availabilities;
    }

    public function getOptionId(): string
    {
        return $this->optionId;
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

}