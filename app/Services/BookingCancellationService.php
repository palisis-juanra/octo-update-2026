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
    public const TIMEZONE_UTC = 'UTC';
    public const BOOKING_NOT_FOUND = 'API return no matching data, invalid booking ID / channel';
    public const ERROR_PREVIOUSLY_CANCELLED = 'PREVIOUSLY CANCELLED';
    public function __construct(
        public TourCMSService $tourCMSService,
        public ProductService $productService,
        public AvailabilityService $availabilityService,
        public JSONLogService $logger)
    {

    }

    public function cancelBooking(Booking $booking, ?string $reason = null, ?bool $force = null): Booking
    {
        // Check if booking can be cancelled
        if ($booking->getCancellable() == 0) {
            $this->logger->info("Booking not cancellable: {$booking->getId()}");
            throw new BookingNotCancellableException;
        }
        
        // Cancel booking with the corresponding booking_uuid
        $bookingData = $this->getBookingDataForCancelBooking($booking->booking_id, $reason);
        
        $cancelBookingResponse = $this->tourCMSService->cancelBooking($bookingData);
        $error = (string) $cancelBookingResponse->error;
        
        if ($error == self::ERROR_PREVIOUSLY_CANCELLED) {
            $this->logger->info("Booking {$booking->getId()} already cancelled, calling show booking to return booking info");
            return $this->getBookingResponse($booking);
        }
        if ($error !== TourCMSService::ERROR_OK) {
            $this->logger->error(self::BOOKING_NOT_FOUND);
            throw new InvalidBookingUUIDException($booking->getUuid());
        }

        $this->logger->info(["cancelBookingResponse" => $cancelBookingResponse]);
        $booking->setStatus(self::STATUS_CANCELLED);
        
        // Update booking information
        Booking::where('uuid', $booking->getUuid())->update(['status' => self::STATUS_CANCELLED]);

        $booking = $this->getBookingResponse($booking);

        return $booking;

    }

    public function getBookingByUuid(string $uuid): Booking
    {
        $booking = Booking::find($uuid);

        if (is_null($booking)) {
            throw new InvalidBookingUUIDException($uuid);
        }

        return $booking;
    }

    public function getBookingResponse(Booking $booking): Booking
    {
        $showBookingResponse = $this->tourCMSService->showBooking($booking->booking_id);
        $this->logger->info(["showBookingResponse" => $showBookingResponse]);

        $product = $this->productService->find($booking->product_id);
        $option = $product->getOptionById($booking->option_id);
        $availability = $this->availabilityService->find(availabilityId: $booking->availability_id);
        $unitItems = json_decode($booking->unit_items, 1);

        $bookingResponse = $this->createFromShowBookingXML(
            $booking->getUuid(),
            $showBookingResponse,
            $product,
            $option,
            $availability,
            $unitItems,
        );

        return $bookingResponse;
    }
   
    public function getBookingDataForCancelBooking(string $bookingId, ?string $reason = null): SimpleXMLElement
    {
        $booking = new SimpleXMLElement('<booking />');
        $booking->addChild('booking_id', $bookingId);
        if (!is_null($reason)) {
            $booking->addChild('note', $reason);
        }

        return $booking;
    }
    
    public function createCancellationObject(SimpleXMLElement $showBookingXML): object
    {
        $cancellation = new stdClass();
        error_log($showBookingXML->booking->cancelled_at_utc_seconds);

        $cancellation->refund = self::REFUND_FULL;
        $cancellation->reason = (string) $showBookingXML->booking->cancel_text ?? null;
        $cancellation->utcCancelledAt =  Booking::createUtcCancelledAt((int) $showBookingXML->booking->cancelled_at_utc_seconds);

        return $cancellation;
    }

    public function createFromShowBookingXML(
        string $bookingUuid,
        SimpleXMLElement $showBookingXML,
        Product $product,
        Option $option,
        Availability $availability,
        array $unitItems,
    ): Booking
    {
        $booking = new Booking();

        $bookingData = $showBookingXML->booking;

        $updatedAtDateTime = new DateTime('now', new DateTimeZone('UTC'));
        $updatedAtDateTime->setTimestamp((int) $bookingData->updated_at_utc_seconds);
        $utcUpdatedAt = DateTimeService::getISO8601DateFormatted($updatedAtDateTime);

        $booking->setBookingId((int) $bookingData->booking_id);
        $booking->setUuid((string) $bookingUuid);
        $booking->setAccountId((int) $bookingData->account_id);
        $booking->setChannelId((int) $bookingData->channel_id);

        $booking->setLeadCustomerId((int) $bookingData->lead_customer_id);
        $booking->setUtcCreatedAt((int) $bookingData->made_date_time_at_utc_seconds);
        $booking->setUtcConfirmedAt((int) $bookingData->confirmed_at_utc_seconds);
        $booking->setUtcUpdatedAt((string) $utcUpdatedAt);

        $booking->setUtcExpiresAt($booking->expiry_date ? strtotime((string) $booking->expiry_date) - strtotime(date('Y-m-d')) : null);
        $booking->setExpirationMinutes((int) $bookingData->hold_time_seconds / 60);

        $booking->setStatus(Booking::getBookingStatus($bookingData));
        $booking->setCancellable((bool) $bookingData->cancellable);
        
        $booking->setProduct($product);
        $booking->setOption($option);
        $booking->setAvailability($availability);
        $booking->setUnits($unitItems);

        if ($booking->getStatus() == self::STATUS_CANCELLED) {
            $booking->setCancellation($this->createCancellationObject($showBookingXML));
        }
        
        if (in_array('VOUCHER', $product->getDeliveryMethods())) {
            $booking->setVoucher([
                'redemptionMethod' => $product->getRedemptionMethod(),
                'utcRedeemedAt' => null,
                'deliveryOptions' => [
                    "deliveryFormat" => $product->getDeliveryFormats()[0]
                ]
            ]);
        }

        return $booking;
    }

}