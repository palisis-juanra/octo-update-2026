<?php

namespace App\Transformers;

use App\Facades\OctoRequestFacade;
use App\Http\Requests\OctoRequest;

class AvailabilityTransformer extends BaseTransformer
{
    public const MODE_BOOKING_AVAILABILITY = 'bookingAvailability';

    protected PricingTransformer $pricingTransformer;

    protected RateTransformer $rateTransformer;

    public function __construct(string $mode = BaseTransformer::BASIC)
    {
        parent::__construct($mode);

        $this->pricingTransformer = new PricingTransformer;
        $this->rateTransformer = new RateTransformer;
    }

    public function basicTransform($availability): array
    {
        return [
            'id' => $availability->getId(),
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
                    'to' => $availability->getOpeningHoursTo(),
                    'from' => $availability->getOpeningHoursFrom(),
                ],
            ],
        ];

        if (OctoRequestFacade::isCapabilityActive(OctoRequest::CAPABILITIES_CONTENT) === true) {
            $data['title'] = $availability->getTitle();
            $data['shortDescription'] = $availability->getShortDescription();
        }

        if (OctoRequestFacade::isCapabilityActive(OctoRequest::CAPABILITIES_PRICING) === true) {
            $pricing = $availability->getPricing();
            $data['pricing'] = $this->pricingTransformer->transform($pricing);

            $unitPricings = $availability->getUnitPricing();
            $data['unitPricing'] = [];

            foreach ($unitPricings as $unitPricing) {
                $unitPricingData = [
                    'unitId' => $unitPricing->getUnitId(),
                    'original' => $unitPricing->getOriginalPrice(),
                    'retail' => $unitPricing->getRetailPrice(),
                    'net' => $unitPricing->getNetPrice(),
                    'currency' => $availability->getCurrency(),
                    'currencyPrecision' => $unitPricing->getCurrencyPrecision(),
                    'includedTaxes' => $unitPricing->getIncludedTaxes(),
                ];

                if (OctoRequestFacade::isCapabilityActive(OctoRequest::CAPABILITIES_BOOKINGCOM_RATES)) {
                    $unitPricingData['rateId'] = $unitPricing->getRateId();
                    $ratesData = [];
                    foreach ($unitPricing->getRates() as $rate) {
                        $ratesData[] = $this->rateTransformer->transform($rate);
                    }
                    $unitPricingData['rates'] = $ratesData;
                }

                $data['unitPricing'][] = $unitPricingData;
            }
        }

        if (OctoRequestFacade::isCapabilityActive(OctoRequest::CAPABILITIES_BOOKINGCOM_RATES) === true) {
            $data['availableRates'] = $availability->getAvailableRates();
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
                    'to' => $availability->getOpeningHoursTo(),
                ],
            ],
        ];
    }
}
