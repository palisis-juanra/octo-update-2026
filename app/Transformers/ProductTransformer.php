<?php

namespace App\Transformers;

use League\Fractal\Resource\Collection;

class ProductTransformer extends BaseTransformer
{
    const MODE_BOOKING_PRODUCT = 'bookingProduct';

    protected OptionTransformer $optionTransformer;

    public function __construct(string $mode)
    {
        parent::__construct($mode);
        $this->optionTransformer = new OptionTransformer(BaseTransformer::FULL_TRANSFORM);
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
        $optionsResource = new Collection($product->getOptions(), $this->optionTransformer);
        $optionsTransformed = $this->manager->createData($optionsResource)->toArray()['data'];

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
            "options" => $optionsTransformed
        ];
    }

    protected function bookingProduct($product): array
    {
        return $this->fullTransform($product);
    }
}
