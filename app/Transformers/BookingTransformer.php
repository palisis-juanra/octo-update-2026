<?php

namespace App\Transformers;

use League\Fractal\Resource\Collection;

class BookingTransformer extends BaseTransformer
{
    protected ProductTransformer $productTransformer;
    protected OptionTransformer $optionTransformer;
    protected AvailabilityTransformer $availabilityTransformer;
    protected ContactTransformer $contactTransformer;
    protected UnitItemTransformer $unitItemTransformer;
    protected VoucherTransformer $voucherTransformer;
    protected BookingCancellationTransformer $bookingCancellationTransformer;

    public function __construct(string $mode)
    {
        parent::__construct($mode);
        $this->productTransformer = new ProductTransformer(ProductTransformer::MODE_BOOKING_PRODUCT);
        $this->availabilityTransformer = new AvailabilityTransformer(AvailabilityTransformer::MODE_BOOKING_AVAILABILITY);
        $this->optionTransformer = new OptionTransformer(BaseTransformer::FULL_TRANSFORM);
        $this->contactTransformer = new ContactTransformer(BaseTransformer::FULL_TRANSFORM);
        $this->unitItemTransformer = new UnitItemTransformer(BaseTransformer::FULL_TRANSFORM);
        $this->voucherTransformer = new VoucherTransformer(BaseTransformer::FULL_TRANSFORM);
        $this->bookingCancellationTransformer = new BookingCancellationTransformer();
    }

    protected function basicTransform($booking): array
    {
        return [
            'id' => $booking->getId(),
            'uuid' => $booking->getUuid(),
        ];
    }

    protected function fullTransform($booking): array
    {
        $product = $booking->getProduct();
        $option = $booking->getOption();
        $availability = $booking->getAvailability();

        $unitItemsResource = new Collection($booking->getUnits(), $this->unitItemTransformer);
        $unitItemsTransformed = $this->manager->createData($unitItemsResource)->toArray()['data'];

        $optionId = $option->getId();
        $option = $this->optionTransformer->transform($option);

        return [
            'id' => $booking->getId(),
            'uuid' => $booking->getUuid(),
            'testMode' => $booking->getTestMode(),
            'resellerReference' => $booking->getResellerReference(),
            'supplierReference' => $booking->getSupplierReference(),
            'status' => $booking->getStatus(),
            'utcCreatedAt' => $booking->getUtcCreatedAt(),
            'utcUpdatedAt' => $booking->getUtcUpdatedAt(),
            'utcExpiresAt' => $booking->getUtcExpiresAt(),
            'utcRedeemedAt' => $booking->getUtcRedeemedAt(),
            'utcConfirmedAt' => $booking->getUtcConfirmedAt(),
            'productId' => $product->getId(),
            'product' => $this->productTransformer->transform($product),
            'optionId' => $optionId,
            'option' => $option,
            'cancellable' => $booking->getCancellable(),
            'cancellation' => null !== $booking->getCancellation() ? $this->bookingCancellationTransformer->transform($booking->getCancellation()) : null,
            'freesale' => $product->getAllowFreesale(),
            'availabilityId' => $availability->getId(),
            'availability' => $this->availabilityTransformer->transform($availability),
            'contact' => $this->contactTransformer->transform($booking->getContact()),
            'notes' => $booking->getNotes(),
            'deliveryMethods' => $product->getDeliveryMethods(),
            'voucher' => (null !== $booking->getVoucher()) ? $this->voucherTransformer->transform($booking->getVoucher()) : null,
            'unitItems' => $unitItemsTransformed,
        ];
    }
}
