<?php

namespace App\Transformers;

class PricingTransformer extends BaseTransformer
{
 
    public function __construct(string $mode = BaseTransformer::FULL_TRANSFORM)
    {
        parent::__construct($mode);
    }

    protected function basicTransform($pricing): array
    {
        return [
            "original" => $pricing->getOriginal(),
            "retail" => $pricing->getRetail(),
            "net" => $pricing->getNet(),
            "currency" => $pricing->getCurrency(),
            "currencyPrecision" => $pricing->getCurrencyPrecision(),
            "includedTaxes" => $pricing->getIncludedTaxes()
        ];
    }

    protected function fullTransform($pricing): array
    {
        return $this->basicTransform($pricing);
    }
}
