<?php

namespace App\Transformers;

use League\Fractal\Resource\Collection;

class VoucherTransformer extends BaseTransformer
{
    protected DeliveryOptionsTransformer $deliveryOptionsTransformer;

    public function __construct(string $mode = BaseTransformer::FULL_TRANSFORM)
    {
        parent::__construct($mode);
        $this->deliveryOptionsTransformer = new DeliveryOptionsTransformer(BaseTransformer::FULL_TRANSFORM);
    }

    public function basicTransform($voucher): array
    {
        $deliveryOptionsResource = new Collection($voucher->getDeliveryOptions(), $this->deliveryOptionsTransformer);
        $deliveryOptionsTransformed = $this->manager->createData($deliveryOptionsResource)->toArray()['data'];

        return [
            'redemptionMethod' => $voucher->getRedemptionMethod(),
            'utcRedeemedAt' => $voucher->getUtcRedeemedAt(),
            'deliveryOptions' => $deliveryOptionsTransformed,
        ];
    }

    public function fullTransform($voucher): array
    {
        return $this->basicTransform($voucher);
    }
}
