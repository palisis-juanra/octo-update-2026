<?php

namespace App\Transformers;

use App\Transformers\BaseTransformer;
use stdClass;

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

        $data = [
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

        if (!empty($availability->getPricing())) {
            $pricing = $availability->getPricing();
            $data['pricing'] = [
                'original' => $pricing->getOriginalPrice(),
                'retail' => $pricing->getRetailPrice(),
                'net' => $pricing->getNetPrice(),
                'currency' => $availability->getCurrency(),
                'currencyPrecision' => $pricing->getCurrencyPrecision()
            ];
        }

        return $data;
    }

    public function calendarTransform($availability): array
    {
        return [
            'localDate' => (string) explode('T', $availability->getLocalDateTimeStart())[0], 
            'available' => $availability->getAvailable(),
            'status' => $availability->getStatus(),
            'vacancies' => $availability->getVacancies(),
            'capacity' => $availability->getCapacity(),
            'openingHours' => [
                 (object) [
                'from' => $availability->getOpeningHoursFrom(),
                'to' => $availability->getOpeningHoursTo() 
                ]
            ]
        ];
    }
}