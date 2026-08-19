<?php

namespace App\Transformers;

class DeliveryOptionsTransformer extends BaseTransformer
{
    public function basicTransform($deliveryOptions): array
    {
        return [
            'deliveryFormat' => $deliveryOptions->getDeliveryFormat(),
            'deliveryValue' => $deliveryOptions->getDeliveryValue(),
        ];
    }

    public function fullTransform($deliveryOptions): array
    {
        return $this->basicTransform($deliveryOptions);
    }
}
