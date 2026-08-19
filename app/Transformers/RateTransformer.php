<?php

namespace App\Transformers;

use App\Models\Rate;

class RateTransformer extends BaseTransformer
{
    public function __construct(string $mode = BaseTransformer::BASIC)
    {
        parent::__construct($mode);
    }

    /**
     * @param  Rate  $rate
     */
    public function basicTransform($rate): array
    {
        return [
            'id' => $rate->getId(),
            'retail' => $rate->getRetailPrice(),
            'net' => $rate->getNetPrice(),
        ];
    }

    public function fullTransform($rate): array
    {
        return $this->basicTransform($rate);
    }
}
