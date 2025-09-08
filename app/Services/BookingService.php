<?php

namespace App\Services;

use App\Builders\BookingBuilder;
use App\Builders\BookingChecker;
use App\Exceptions\InvalidBookingUUIDException;
use App\Factories\BookingFactory;
use App\Models\Availability\Availability;
use App\Models\Booking;
use FastRoute\RouteParser\Std;
use SimpleXMLElement;
use stdClass;

class BookingService
{
    public const TCMS_BOOKING_STATUS_DELETED = '-1';
    public BookingBuilder $bookingBuilder;
    public BookingChecker $checker;

    public function __construct(
        public TourCMSService $tourCMSService,
        public JSONLogService $logger,
        public ProductService $productService,
        public AvailabilityService $availabilityService,
    )
    { 
        $this->bookingBuilder = new BookingBuilder();
        $this->checker = new BookingChecker($this->logger);
    }

    public function getBookingByUuid(string $uuid): Booking
    {
        $booking = Booking::find($uuid);
        if (is_null($booking)) {
            throw new InvalidBookingUUIDException($uuid);
        }
        return $booking;
    }

    public function getBooking(Booking $booking, bool $mandatoryOptions = true): Booking
    {
        $showBookingResponse = $this->tourCMSService->showBooking($booking->getBookingId());
        $this->logger->info(["showBookingResponse" => $showBookingResponse]);
        
        // Deleted booking have no components, we must check that components exists
        $this->checkIfBookingHaveBeenDeleted($showBookingResponse, $booking->getUuid());

        $product = $this->productService->find($booking->product_id);
        $option = null;
        if ($mandatoryOptions) {
            $option = $product->getOptionById($booking->option_id);
        }
        $availability = new Availability();
        $availability = $this->availabilityService->generateAvailabilityFromBookingXML($product, $showBookingResponse);

        return BookingFactory::createFromShowBookingXML(
            $booking->getUuid(),
            $showBookingResponse, 
            $product, 
            $availability,
            $booking->unit_items,
            $option
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

    public function getBookingObjectFromJSON(array $dataArray): stdClass
    {
        return $this->bookingBuilder->build($dataArray);
    }

    public function updateStoredJsonWithNewInformation(stdClass $storedBooking, stdClass $updatedBooking): stdClass
    {
        $booking = $this->checker->updateStoredJsonWithNewInformation($storedBooking, $updatedBooking);
        return $booking;
    }
}