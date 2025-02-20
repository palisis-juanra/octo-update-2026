<?php

namespace App\Transformers;

class ProductPricingTransformer extends BaseTransformer
{
 
    public function __construct(string $mode = BaseTransformer::FULL_TRANSFORM)
    {
        parent::__construct($mode);
    }

    protected function basicTransform($productPricing): array
    {
        return [
            'defaultCurrency' => $productPricing->getDefaultCurrency(),
            'availableCurrencies' => $productPricing->getAvailableCurrencies(),
            'pricingPer' => $productPricing->getPricingPer()
        ];
    }

    protected function fullTransform($productPricing): array
    {
        return $this->basicTransform($productPricing);
    }
}
