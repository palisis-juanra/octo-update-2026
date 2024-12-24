<?php

namespace App\Transformers;

class CalendarAvailabilityTransformer extends BaseTransformer
{
    protected OpeningHoursTransformer $openingHoursTransformer;
    public function __construct(string $mode = BaseTransformer::BASIC)
    {
        parent::__construct($mode);
        $this->openingHoursTransformer = new OpeningHoursTransformer(BaseTransformer::FULL_TRANSFORM);
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
        $openingHours = [];
        foreach ($calendarAvailability->getOpeningHours() as $openingHoursObject) {
            $openingHours[] = $this->openingHoursTransformer->transform($openingHoursObject);
        }
        return [
            'localDate' => $calendarAvailability->getLocalDate(),
            'available' => $calendarAvailability->getAvailable(),
            "status" => $calendarAvailability->getStatus(),
            "vacancies" => $calendarAvailability->getVacancies(),
            "capacity" => $calendarAvailability->getCapacity(),
            "openingHours" => $openingHours,
        ];
    }
}
