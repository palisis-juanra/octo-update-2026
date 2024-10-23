<?php

namespace App\Transformers;

use App\Models\Product;

class ProductTransformer extends BaseTransformer
{
    public function __construct(string $mode)
    {
        parent::__construct($mode);
    }

    protected function basicTransform($product): array
    {
        return [
            'id' => $product->getId(),
            'internalName' => $product->getInternalName(),
        ];
    }

    protected function fullTransform($product): array
    {
        return [
            'id' => $product->getId(),
            'internalName' => $product->getInternalName(),
            "reference" => $product->getReference(),
            "locale" => $product->getLocale(),
            "timeZone" => $product->getTimeZone(),
            "allowFreesale" => $product->getAllowFreesale(),
            "instantConfirmation" => $product->getInstantConfirmation(),
            "instantDelivery" => $product->getInstantDelivery(),
            "availabilityRequired" => $product->getAvailabilityRequired(),
            "availabilityType" => $product->getAvailabilityType(),
            "deliveryFormats" => $product->getDeliveryFormats(),
            "deliveryMethods" => $product->getDeliveryMethods(),
            "redemptionMethod" => $product->getRedemptionMethod(),
            "options" => $product->getOptions()
        ];
    }
}
