<?php

namespace App\Models\Availability;

use App\Interfaces\BaseAvailabilityRequest;
use App\Services\DateTimeService;
use App\Services\OptionService;
use App\Services\TourCMSService;
use DateTime;
use stdClass;

class AvailabilityCalendarRequest extends BaseAvailabilityRequest
{
    public string $tourId;
    public string $optionId;
    public string $localDateStart;
    public string $localDateEnd;
    protected array $units;

    public function __construct(string $tourId, string $optionId, string $localDateStart, string $localDateEnd = '', ?array $units = null)
    {
        $this->tourId = $tourId;
        $this->optionId = $optionId;
        $this->localDateStart = $localDateStart;
        $this->localDateEnd = $localDateEnd;
        $this->units = $units;
    }

    /**
     * Fetch dates and deals info from API and return the availabilities
     * 
     * @return Availability[]
     */
    public function getAvailabilities(TourCMSService $tourCMSService): array
    {
        $datesAndDeals = $this->fetchDatesAndDealsFromAPI($tourCMSService);
        $availabilities = $this->getAvailabilitiesFromDatesAndDeals($datesAndDeals);

        return $availabilities;
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

    protected function getAvailabilitiesFromDatesAndDeals(array $datesAndDeals): array
    {
        $availabilities = [];

        foreach ($datesAndDeals as $date) {
            $availability = new CalendarAvailability();

            $openingHours = (object) [
                'from' => (string) $date->start_time ?? '00:00',
                'to' => (string) $date->end_time ?? '23:59'
            ];

            $availability->setLocalDate($date->start_date)
                ->setAvailable($date->spaces_remaining > 0)
                ->setStatus($this->getOctoStatusFromTourCMSStatus((string) $date->status))
                ->setVacancies($date->spaces_remaining != "UNLIMITED" ? (int) $date->spaces_remaining : null)
                ->setCapacity(null)
                ->setOpeningHours([$openingHours]);
            
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