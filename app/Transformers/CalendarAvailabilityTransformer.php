<?php

namespace App\Transformers;

class CalendarAvailabilityTransformer extends BaseTransformer
{
    public function __construct(string $mode = BaseTransformer::BASIC)
    {
        parent::__construct($mode);
    }

    protected function basicTransform($calendarAvailability): array
    {
        return [
            'localDate' => $calendarAvailability->getLocalDate(),
            'available' => $calendarAvailability->getAvailable(),
        ];
    }

    protected function fullTransform($calendarAvailability): array
    {
        return [
            'localDate' => $calendarAvailability->getLocalDate(),
            'available' => $calendarAvailability->getAvailable(),
            "status" => $calendarAvailability->getStatus(),
            "vacancies" => $calendarAvailability->getVacancies(),
            "capacity" => $calendarAvailability->getCapacity(),
            "openingHours" => $calendarAvailability->getOpeningHours(),
        ];
    }
}
