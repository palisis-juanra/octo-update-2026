<?php

namespace App\Models\Availability;

use App\Services\OptionService;
use App\Services\TourCMSService;

class AvailabilityCalendarRequest
{
    const OCTO_STATUS_AVAILABLE = 'AVAILABLE';
    const OCTO_STATUS_CLOSED = 'CLOSED';
    const TCMS_STATUS_OPEN = 'OPEN';
    const TCMS_STATUS_ASKFIRST = 'ASKFIRST';
    const START_TIME_DEFAULT = '00:00';
    const END_TIME_DEFAULT = '23:59';
    const SPACES_REMAINING_UNLIMITED = 'UNLIMITED';
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

    public function fetchDatesAndDealsFromAPI(TourCMSService $tourCMSService): array
    {
        $mappingQueryString = OptionService::getMappingQueryString($this->optionId);

        $response = $tourCMSService->showTourDatesAndDeals($this->tourId, $this->localDateStart, $this->localDateEnd, $mappingQueryString);

        if (!isset($response->dates_and_prices)) {
            return [];
        }

        $datesAndDeals = $tourCMSService->getArrayFromXmlNode($response->dates_and_prices, 'date');

        return $datesAndDeals;
    }

    public function getAvailabilitiesFromDatesAndDeals(array $datesAndDeals): array
    {
        $availabilities = [];

        foreach ($datesAndDeals as $date) {
            $availability = new CalendarAvailability();

            $openingHours = new OpeningHours(
                (string) $date->start_time ?? self::START_TIME_DEFAULT,
                (string) $date->end_time ?? self::END_TIME_DEFAULT
            );

            $availability->setLocalDate($date->start_date)
                ->setAvailable($this->checkSpacesRemaining($date))
                ->setStatus($this->getOctoStatusFromTourCMSStatus((string) $date->status))
                ->setVacancies($date->spaces_remaining != self::SPACES_REMAINING_UNLIMITED ? (int) $date->spaces_remaining : null)
                ->setCapacity(null)
                ->setOpeningHours([$openingHours]);
            
            $availabilities[] = $availability;
        }

        return $availabilities;
    }

    public function getTourId()
    {
        return $this->tourId;
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
    
    public function getUnits(): array
    {
        return $this->units;
    }

    public function setUnits($units)
    {
        $this->units = $units;

        return $this;
    }
    
    public function getOctoStatusFromTourCMSStatus(string $status): string
    {
        if ($status == self::TCMS_STATUS_OPEN || $status == self::TCMS_STATUS_ASKFIRST) {
            return self::OCTO_STATUS_AVAILABLE;
        }

        return self::OCTO_STATUS_CLOSED;
    }

    public function checkSpacesRemaining(\SimpleXMLElement $date): bool
    {
        $spacesRequired = $date->min_booking_size;
        if (!empty($this->getUnits())) {
            $spacesRequired = 0;
            foreach ($this->getUnits() as $unit) {
                $spacesRequired += (int) $unit['quantity'];
            }
        }
        return $date->spaces_remaining >= $spacesRequired;
    }
}