<?php

namespace App\Services;

use App\Exceptions\InvalidBookingUUIDException;
use App\Models\Availability\Availability;
use App\Models\Booking;
use SimpleXMLElement;

class BookingService
{
    public function __construct(
        public TourCMSService $tourCMSService,
        public JSONLogService $logger,
        public ProductService $productService,
        public AvailabilityService $availabilityService,
    )
    {
        
    }

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
        
        $product = $this->productService->find($booking->product_id);
        $option = $product->getOptionById($booking->option_id);
        $availability = new Availability();
        $availability = $this->availabilityService->generateAvailabilityFromBookingXML($showBookingResponse);
        $unitItems = json_decode($booking->unit_items, 1);

        return Booking::createFromShowBookingXML(
            $booking->getUuid(),
            $showBookingResponse, 
            $product, 
            $option, 
            $availability, 
            $unitItems
        );
    }
}