<?php

namespace App\Transformers;

use App\Facades\OctoRequestFacade;
use App\Http\Requests\OctoRequest;
use App\Models\Pricing;

class PricingTransformer extends BaseTransformer
{
    protected RateTransformer $rateTransformer;

    public function __construct(string $mode = BaseTransformer::FULL_TRANSFORM)
    {
        parent::__construct($mode);

        $this->rateTransformer = new RateTransformer($mode);
    }

    /**
     * Summary of basicTransform
     * @param Pricing $pricing
     * @return array
     */
    protected function basicTransform($pricing): array
    {
        $data =  [
            "original" => $pricing->getOriginal(),
            "retail" => $pricing->getRetail(),
            "net" => $pricing->getNet(),
            "currency" => $pricing->getCurrency(),
            "currencyPrecision" => $pricing->getCurrencyPrecision(),
            "includedTaxes" => $pricing->getIncludedTaxes()
        ];

        if (OctoRequestFacade::isCapabilityActive(OctoRequest::CAPABILITIES_BOOKINGCOM_RATES)) {
            $data["rateId"] = $pricing->getRateId();
            $rates = [];
            foreach ($pricing->getRates() as $rate) {
                $rates[] = $this->rateTransformer->transform($rate);
            }
            $data["rates"] = $rates;
        }

        return $data;
    }

    protected function fullTransform($pricing): array
    {
        return $this->basicTransform($pricing);
    }
}
