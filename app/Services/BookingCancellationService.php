<?php

namespace App\Services;

use App\Builders\BookingBuilder;
use App\Builders\BookingChecker;
use App\Exceptions\InvalidBookingUUIDException;
use App\Exceptions\NoMatchingDataException;
use App\Models\Booking;
use SimpleXMLElement;

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
        public JSONLogService $logger,
        public BookingBuilder $bookingBuilder,
        public BookingChecker $checker
    )
    {
        parent::__construct($tourCMSService, $logger, $productService, $availabilityService, $bookingBuilder, $checker);
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
        foreach($booking->getUnits() as $unit) {
            $unit->setStatus(Booking::STATUS_CANCELLED);
        }

        Booking::where('uuid', $booking->getUuid())->update(['status' => self::STATUS_CANCELLED]);
    }
   
    
    public function shouldWeCancelBooking(Booking $booking): bool
    {
        $status = $booking->getStatus();
        if($status === Booking::STATUS_REDEEMED) {
            throw new \App\Exceptions\BookingAlreadyRedeemedException();
        }
        return $status == Booking::STATUS_CONFIRMED || $status === Booking::STATUS_PENDING;
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