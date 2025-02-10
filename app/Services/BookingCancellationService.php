<?php

namespace App\Services;

use App\Exceptions\BookingNotCancellableException;
use App\Exceptions\InvalidBookingUUIDException;
use App\Exceptions\NoMatchingDataException;
use App\Models\Availability\Availability;
use App\Models\Booking;
use App\Models\BookingCancellation;
use App\Models\Option;
use App\Models\Product;
use DateTime;
use DateTimeZone;
use SimpleXMLElement;
use stdClass;

class BookingCancellationService extends BookingService
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
        parent::__construct($tourCMSService, $logger, $productService, $availabilityService);
    }

    /**
     * Cancel a booking
     * @param \App\Models\Booking $booking
     * @param mixed $reason
     * @param mixed $force
     * @throws \App\Exceptions\InvalidBookingUUIDException
     * @return bool
     */
    public function cancelBooking(Booking $booking, ?string $reason = null): bool
    {   
            
        try {
            $bookingData = $this->getCancelBookingXMLRequest($booking->booking_id, $reason);
            $cancelBookingXML = $this->tourCMSService->cancelBooking($bookingData);
            $this->logger->info(["Cancel Booking XML Response" => $cancelBookingXML]);
            
            if ((string) $cancelBookingXML->error == TourCMSService::INVALID_BOOKING_ID) {
                throw new InvalidBookingUUIDException($booking->getUuid());
            }

            return true;

        } catch (NoMatchingDataException) {
            throw new InvalidBookingUUIDException($booking->getUuid());
        }
    }

    /**
     * Delete a temporary booking
     * @param \App\Models\Booking $booking
     * @throws \App\Exceptions\InvalidBookingUUIDException
     * @return bool
     */
    public function deleteBooking(Booking $booking): bool
    {
        try {
            $deleteBookingXML = $this->tourCMSService->deleteBooking(bookingId: $booking->getBookingId());
            $this->logger->info(["Delete Booking XML Response" => $deleteBookingXML]);
            
            if ((string) $deleteBookingXML->error == TourCMSService::INVALID_BOOKING_ID) {
                throw new InvalidBookingUUIDException($booking->getUuid());
            }

            return true;

        } catch (NoMatchingDataException) {
            throw new InvalidBookingUUIDException($booking->getUuid());
        }
    }

    public function updateBookingStatusToCancelled(Booking $booking): void
    {
        $booking->setStatus(Booking::STATUS_CANCELLED);

        if ($booking->getStatus() == self::STATUS_CANCELLED) {
            Booking::where('uuid', $booking->getUuid())->update(['status' => self::STATUS_CANCELLED]);
        }
    }
   
    
    public function shouldWeCancelBooking(Booking $booking): bool
    {
        $status = $booking->getStatus();
        return $status == Booking::STATUS_CONFIRMED || $status === Booking::STATUS_ON_HOLD;
    }

    protected function getCancelBookingXMLRequest(string $bookingId, ?string $reason = null): SimpleXMLElement
    {
        $booking = new SimpleXMLElement('<booking />');
        $booking->addChild('booking_id', $bookingId);
        if (!is_null($reason)) {
            $booking->addChild('note', $reason);
        }
    
        return $booking;
    }
}