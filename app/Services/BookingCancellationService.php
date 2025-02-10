<?php

namespace App\Services;

use App\Exceptions\BookingNotCancellableException;
use App\Exceptions\InvalidBookingUUIDException;
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

    public function cancelBooking(Booking $booking, ?string $reason = null, ?bool $force = null): bool
    {   

        if ($booking->getStatus() == Booking::STATUS_CONFIRMED) {
            
            $this->logger->info("Booking {$booking->getUuid()} is confirmed, calling cancel booking endpoint");
            
            // Cancel booking with the corresponding booking_uuid
            $bookingData = $this->getCancelBookingXMLRequest($booking->booking_id, $reason);

            $cancelBookingXML = $this->tourCMSService->cancelBooking($bookingData);
            $this->logger->info(["Cancel Booking XML Response" => $cancelBookingXML]);
            $error = (string) $cancelBookingXML->error;
            
            if ($error == self::ERROR_PREVIOUSLY_CANCELLED) {
                $this->logger->info("Booking {$booking->getId()} already cancelled");
                return true;
            }

        } else {
            $this->logger->info("Booking {$booking->getUuid()} is temporary, calling delete booking endpoint");
            $deleteBookingXML = $this->tourCMSService->deleteBooking(bookingId: $booking->getBookingId());
            $this->logger->info(["Delete Booking XML Response" => $deleteBookingXML]);
            $error = (string) $deleteBookingXML->error;
        }

        if ($error !== TourCMSService::ERROR_OK) {
            $this->logger->error(self::BOOKING_NOT_FOUND);
            throw new InvalidBookingUUIDException($booking->getUuid());
        }

        return true;
    }

    public function updateBookingStatusToCancelled(Booking $booking): void
    {
        $booking->setStatus(Booking::STATUS_CANCELLED);

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