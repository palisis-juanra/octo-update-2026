<?php

namespace App\Transformers;

use App\Facades\OctoRequestFacade;
use App\Http\Requests\OctoRequest;
use League\Fractal\Resource\Collection;

class ProductTransformer extends BaseTransformer
{
    public const MODE_BOOKING_PRODUCT = 'bookingProduct';

    protected OptionTransformer $optionTransformer;
    protected ProductContentTransformer $productContentTransformer;
    protected ProductPricingTransformer $productPricingTransformer;

    public function __construct(string $mode)
    {
        parent::__construct($mode);
        $this->optionTransformer = new OptionTransformer(BaseTransformer::FULL_TRANSFORM);
        $this->productContentTransformer = new ProductContentTransformer(BaseTransformer::FULL_TRANSFORM);
        $this->productPricingTransformer = new ProductPricingTransformer();
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

        $data = [
            "id" => $product->getId(),
            "internalName" => $product->getInternalName(),
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

        if (true === OctoRequestFacade::isCapabilityActive(OctoRequest::CAPABILITIES_CONTENT)){
            $contentData = $this->productContentTransformer->transform($product->getContent());
            $data = array_merge($data, $contentData);
        }
    
        if (true === OctoRequestFacade::isCapabilityActive(OctoRequest::CAPABILITIES_PRICING)){
            $pricingData = $this->productPricingTransformer->transform($product->getPricing());
            $data = array_merge($data, $pricingData);
        }

        return $data;
    }

    protected function bookingProduct($product): array
    {
        return $this->fullTransform($product);
    }
}
