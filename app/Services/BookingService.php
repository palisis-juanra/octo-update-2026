<?php

namespace App\Services;

use App\Exceptions\InvalidBookingUUIDException;
use App\Factories\BookingFactory;
use App\Models\Availability\Availability;
use App\Models\Booking;
use SimpleXMLElement;

class BookingService
{
    public const TCMS_BOOKING_STATUS_DELETED = '-1';

    public function __construct(
        public TourCMSService $tourCMSService,
        public JSONLogService $logger,
        public ProductService $productService,
        public AvailabilityService $availabilityService,
    )
    { }

    public function getBookingByUuid(string $uuid): Booking
    {
        $booking = Booking::find($uuid);
        if (is_null($booking)) {
            throw new InvalidBookingUUIDException($uuid);
        }
        return $booking;
    }

    public function getBooking(Booking $booking): Booking
    {
        $showBookingResponse = $this->tourCMSService->showBooking($booking->getBookingId());
        $this->logger->info(["showBookingResponse" => $showBookingResponse]);
        
        // Deleted booking have no components, we must check that components exists
        $this->checkIfBookingHaveBeenDeleted($showBookingResponse, $booking->getUuid());

        $product = $this->productService->find($booking->product_id);
        $option = $product->getOptionById($booking->option_id);
        $availability = new Availability();
        $availability = $this->availabilityService->generateAvailabilityFromBookingXML($showBookingResponse);

        return BookingFactory::createFromShowBookingXML(
            $booking->getUuid(),
            $showBookingResponse, 
            $product, 
            $option, 
            $availability,
            $booking->unit_items
        );
    }

    /**
     * @throws \App\Exceptions\InvalidBookingUUIDException
     * @return bool
     */
    protected function checkIfBookingHaveBeenDeleted(SimpleXMLElement $showBooking, string $uuid): bool
    {
        if (empty($showBooking->booking->components)) {
            throw new InvalidBookingUUIDException($uuid);
        }

        return true;
    }
}