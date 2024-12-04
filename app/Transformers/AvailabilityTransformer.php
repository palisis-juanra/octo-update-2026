<?php

namespace App\Transformers;

use App\Transformers\BaseTransformer;

class AvailabilityTransformer extends BaseTransformer
{
    const MODE_BOOKING_AVAILABILITY = 'bookingAvailability';

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
            'openingHours' => [
                [
                    'to' => $availability->getOpeningHoursFrom(),
                    'from' => $availability->getOpeningHoursTo()
                ]
            ]
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

    public function bookingAvailability($availability): array
    {
        return [
            'id' => $availability->getId(),
            'localDateTimeStart' => $availability->getLocalDateTimeStart(), 
            'localDateTimeEnd' => $availability->getLocalDateTimeEnd(),
            'allDay' => $availability->getAllDay(),
            'openingHours' => [
                [
                    'from' => $availability->getOpeningHoursFrom(),
                    'to' => $availability->getOpeningHoursTo()
                ]
            ]
        ];
    }
}