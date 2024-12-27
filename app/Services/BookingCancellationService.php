<?php

namespace App\Services;

use App\Exceptions\BookingNotCancellableException;
use App\Exceptions\InvalidBookingUUIDException;
use App\Models\Availability\Availability;
use App\Models\Booking;
use App\Models\Option;
use App\Models\Product;
use DateTime;
use DateTimeZone;
use SimpleXMLElement;
use stdClass;

class BookingCancellationService
{
    public const STATUS_CANCELLED = 'CANCELLED';
    public const REFUND_FULL = 'FULL';
    public const BOOKING_NOT_FOUND = 'API return no matching data, invalid booking ID / channel';
    public const ERROR_PREVIOUSLY_CANCELLED = 'PREVIOUSLY CANCELLED';
    public function __construct(
        public TourCMSService $tourCMSService,
        public ProductService $productService,
        public AvailabilityService $availabilityService,
        public JSONLogService $logger)
    {

    }

    public function cancelBooking(Booking $booking, ?string $reason = null, ?bool $force = null): void
    {   
        // Cancel booking with the corresponding booking_uuid
        $bookingData = $this->getCancelBookingXMLRequest($booking->booking_id, $reason);
        
        $cancelBookingObject = $this->tourCMSService->cancelBooking($bookingData);
        $this->logger->info(["cancelBookingObject" => $cancelBookingObject]);
        $error = (string) $cancelBookingObject->error;
        
        if ($error == self::ERROR_PREVIOUSLY_CANCELLED) {
            $this->logger->info("Booking {$booking->getId()} already cancelled, calling show booking to return booking info");
            return;
        }
        if ($error !== TourCMSService::ERROR_OK) {
            $this->logger->error(self::BOOKING_NOT_FOUND);
            throw new InvalidBookingUUIDException($booking->getUuid());
        }

    }

    public function getBookingByUuid(string $uuid): Booking
    {
        $booking = Booking::find($uuid);

        if (is_null($booking)) {
            throw new InvalidBookingUUIDException($uuid);
        }

        return $booking;
    }

    public function getBookingObject(Booking $booking): Booking
    {
        $showBookingResponse = $this->tourCMSService->showBooking($booking->booking_id);
        $this->logger->info(["showBookingResponse" => $showBookingResponse]);

        $product = $this->productService->find($booking->product_id);
        $option = $product->getOptionById($booking->option_id);

        $tourId = explode('|', explode('_', $booking->product_id)[2])[0];
        $availability = $this->availabilityService->find($booking->availability_id, $tourId, $booking->option_id);
        $unitItems = json_decode($booking->unit_items, 1);

        $bookingObject = Booking::createFromShowBookingXML(
            $booking->getUuid(),
            $showBookingResponse,
            $product,
            $option,
            $availability,
            $unitItems,
        );

        return $bookingObject;
    }

    public function isBookingCancellable(Booking $bookingObject): void
    {
        if ($bookingObject->getCancellable() == 0) {
            $this->logger->info("Booking not cancellable: {$bookingObject->getId()}");
            throw new BookingNotCancellableException;
        }
    }

    public function updateBookingStatusToCancelled(Booking $booking): void
    {
        if ($booking->getStatus() == self::STATUS_CANCELLED) {
            Booking::where('uuid', $booking->getUuid())->update(['status' => self::STATUS_CANCELLED]);
        }
    }
   
    public function getCancelBookingXMLRequest(string $bookingId, ?string $reason = null): SimpleXMLElement
    {
        $booking = new SimpleXMLElement('<booking />');
        $booking->addChild('booking_id', $bookingId);
        if (!is_null($reason)) {
            $booking->addChild('note', $reason);
        }

        return $booking;
    }
}