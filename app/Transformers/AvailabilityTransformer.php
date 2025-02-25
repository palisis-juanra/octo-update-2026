<?php

namespace App\Transformers;

use App\Facades\OctoRequestFacade;
use App\Http\Requests\OctoRequest;
use App\Transformers\BaseTransformer;

class AvailabilityTransformer extends BaseTransformer
{
    public const MODE_BOOKING_AVAILABILITY = 'bookingAvailability';

    protected PricingTransformer $pricingTransformer;

    public function __construct(string $mode = BaseTransformer::BASIC)
    {
        parent::__construct($mode);

        $this->pricingTransformer = new PricingTransformer();
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
        
        if (true === OctoRequestFacade::isCapabilityActive(OctoRequest::CAPABILITIES_CONTENT)) {
            $data['title'] = $availability->getTitle();
            $data['shortDescription'] = $availability->getShortDescription();
        }

        if (true === OctoRequestFacade::isCapabilityActive(OctoRequest::CAPABILITIES_PRICING)) {
            $pricing = $availability->getPricing();
            $data['pricing'] = $this->pricingTransformer->transform($pricing);

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