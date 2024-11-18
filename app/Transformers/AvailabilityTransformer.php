<?php

namespace App\Transformers;

use App\Transformers\BaseTransformer;

class AvailabilityTransformer extends BaseTransformer
{
    public function __construct(string $mode = BaseTransformer::BASIC)
    {
        parent::__construct($mode);
    }

    public function basicTransform($availability): array
    {
        return [
            'id' => $availability->getId()
        ];
    }

    public function fullTransform($availability): array
    {
        return [
            'id' => $availability->getId(),
            'localDateTimeStart' => $availability->getLocalDateTimeStart(), 
            'localDateTimeEnd' => $availability->getLocalDateTimeEnd(),
            'allDay' => $availability->getAllDay(),
            'available' => $availability->getAvailable(),
            'status' => $availability->getStatus(),
            'vacancies' => $availability->getVacancies(),
            'capacity' => $availability->getCapacity(),
            'maxUnits' => $availability->getMaxUnits(),
            'utcCutoffAt' => $availability->getUtcCutoffAt(),
            'openingHoursFrom' => $availability->getOpeningHoursFrom(),
            'openingHoursTo' => $availability->getOpeningHoursTo()  
        ];
    }
}