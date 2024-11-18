<?php

namespace App\Models\Availability;

use App\Interfaces\BaseAvailabilityRequest;
use App\Services\TourCMSService;
use DateTime;

class AvailabilityRequest implements BaseAvailabilityRequest
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
        $response = $tourCMSService->showTourDepartures($this->tourId,$this->localDateStart, $this->localDateEnd);

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
            
            $availability = new Availability(
                (string) $departure->departure_id,
                $this->getISODateTimeString((string) $departure->start_date, '00', '00'),
                $this->getISODateTimeString((string) $departure->end_date, '23', '59'),
                false,
                $departure->spaces_remaining > 0,
                $this->getOctoStatusFromTourCMSStatus((string) $departure->status),
                null,
                null,
                999 /* tour max_booking_size */,
                'CUTOFF',
                $departure->start_time ?? '00:00',
                $departure->end_time ?? '23:59'                
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

    public function setMaxUnits(int $maxUnits): void
    {
        $this->maxUnits = $maxUnits;
    }

    public function getMaxUnits(): int
    {
        return $this->maxUnits;
    }

    public function setCutoff(string $cutoff): void
    {
        $this->cutoff = $cutoff;
    }

    public function getCutoff(): string
    {
        return $this->cutoff;
    }

    public function getOctoStatusFromTourCMSStatus(string $status): string
    {
        if ($status == self::TCMS_STATUS_OPEN || $status == self::TCMS_STATUS_ASKFIRST) {
            return self::OCTO_STATUS_AVAILABLE;
        }

        return self::OCTO_STATUS_CLOSED;
    }

    public function getISODateTimeString(string $day = 'now', string $hour = '00', string $minutes = '00'): string
    {
        $dateTime = new DateTime($day);
        $dateTime->setTime($hour, $minutes);
        
        return $dateTime->format(DateTime::ATOM);
    }
}