<?php

namespace App\Models\Availability;

use App\Interfaces\BaseAvailabilityRequest;
use App\Services\DateTimeService;
use App\Services\OptionService;
use App\Services\TourCMSService;
use DateTime;

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

    public function getCalendarAvailabilities(TourCMSService $tourCMSService): array
    {
        $datesAndDeals = $this->fetchDatesAndDealsFromAPI($tourCMSService);
        $availabilities = $this->getAvailabilitiesFromDatesAndDeals($datesAndDeals);

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

    protected function fetchDatesAndDealsFromAPI(TourCMSService $tourCMSService): array
    {
        $mappingQueryString = OptionService::getMappingQueryString($this->optionId);

        $response = $tourCMSService->showTourDatesAndDeals($this->tourId, $this->localDateStart, $this->localDateEnd, $mappingQueryString);

        if (!isset($response->dates_and_prices)) {
            return [];
        }

        $datesAndDeals = $tourCMSService->getArrayFromXmlNode($response->dates_and_prices, 'date');

        return $datesAndDeals;
    }

    protected function getAvailabilitiesFromDepartures(array $departures)
    {   
        $availabilities = [];
        foreach ($departures as $departure) {
            
            $availability = new Availability(
                (string) $departure->departure_id,
                DateTimeService::getISODateTimeString((string) $departure->start_date, '00', '00'),
                DateTimeService::getISODateTimeString((string) $departure->end_date, '23', '59'),
                false,
                $departure->spaces_remaining > 0,
                $this->getOctoStatusFromTourCMSStatus((string) $departure->status),
                null,
                null,
                $this->maxUnits,
                $this->cutoff,
                $departure->start_time ?? '00:00',
                $departure->end_time ?? '23:59'                
            );
            $availabilities[] = $availability;
            
        } 

        return $availabilities;
    }

    protected function getAvailabilitiesFromDatesAndDeals(array $datesAndDeals): array
    {
        $availabilities = [];

        foreach ($datesAndDeals as $date) {
            $availability = new Availability(
                "",
                DateTimeService::getISODateTimeString((string) $date->start_date, '00', '00'),
                DateTimeService::getISODateTimeString((string) $date->end_date, '23', '59'),
                false,
                $date->spaces_remaining > 0,
                $this->getOctoStatusFromTourCMSStatus((string) $date->status),
                $date->spaces_remaining != "UNLIMITED" ? (int) $date->spaces_remaining : null,
                null,
                999 /* tour max_booking_size */,
                'CUTOFF',
                $date->start_time ?? '00:00',
                $date->end_time ?? '23:59'                
            );
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