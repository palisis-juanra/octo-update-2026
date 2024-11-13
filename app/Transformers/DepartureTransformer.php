<?php

namespace App\Transformers;

use App\Transformers\BaseTransformer;

class DepartureTransformer extends BaseTransformer
{
    public function __construct(string $mode = BaseTransformer::BASIC)
    {
        parent::__construct($mode);
    }

    public function basicTransform($departure): array
    {
        return [
            'id' => $departure->getId()
        ];
    }

    public function fullTransform($departure): array
    {
        return [
            'id' => $departure->getId(),
            'localDateTimeStart' => $departure->getLocalDateTimeStart, 
            'localDateTimeEnd' => $departure->getLocalDateTimeEnd,
            'allDay' => $departure->getAllDay(),
            'available' => $departure->getAvailable(),
            'status' => $departure->getStatus(),
            'vacancies' => $departure->getVacancies(),
            'capacity' => $departure->getCapacity(),
            'maxUnits' => $departure->getMaxUnits(),
            'utcCutoffAt' => $departure->getUtcCutoffAt(),
            'openingHoursFrom' => $departure->getOpeningHoursFrom(),
            'openingHoursTo' => $departure->getOpeningHoursTo()  
        ];
    }
}