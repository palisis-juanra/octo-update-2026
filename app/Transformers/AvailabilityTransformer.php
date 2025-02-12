<?php

namespace App\Transformers;

use App\Facades\OctoRequestFacade;
use App\Http\Requests\OctoRequest;
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
        
        if (true === OctoRequestFacade::isContentRequired()) {
            $data['title'] = $availability->getTitle();
            $data['shortDescription'] = $availability->getShortDescription();
        }

        if (true === OctoRequestFacade::isPricingRequired()) {
            $pricing = $availability->getPricing();
            $data['pricing'] = [
                'original' => $pricing->getOriginalPrice(),
                'retail' => $pricing->getRetailPrice(),
                'net' => $pricing->getNetPrice(),
                'currency' => $availability->getCurrency(),
                'currencyPrecision' => $pricing->getCurrencyPrecision()
            ];

            $unitPricings = $availability->getUnitPricing();
            $data['unitPricing'] = [];

            foreach ($unitPricings as $unitPricing) {
                $data['unitPricing'][] = [
                    'unitId' => $unitPricing->getUnitId(),
                    'retail' => $unitPricing->getRetailPrice(),
                    'net' => $unitPricing->getNetPrice(),
                    'currency' => $availability->getCurrency(),
                    'currencyPrecision' => $unitPricing->getCurrencyPrecision()
                ];
            }            
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