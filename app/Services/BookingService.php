<?php

namespace App\Services;

use App\Builders\BookingBuilder;
use App\Builders\BookingChecker;
use App\Exceptions\InvalidBookingUUIDException;
use App\Factories\BookingFactory;
use App\Models\Availability\Availability;
use App\Models\Booking;
use SimpleXMLElement;
use stdClass;

class BookingService
{
    public const TCMS_BOOKING_STATUS_DELETED = '-1';

    public function __construct(
        public TourCMSService $tourCMSService,
        public JSONLogService $logger,
        public ProductService $productService,
        public AvailabilityService $availabilityService,
        public BookingBuilder $bookingBuilder,
        public BookingChecker $checker
    )
    {}

    public function getBookingByUuid(string $uuid): Booking
    {
        $booking = Booking::find($uuid);
        if (is_null($booking)) {
            throw new InvalidBookingUUIDException($uuid);
        }
        return $booking;
    }

    public function getBooking(Booking $booking, bool $fillableFromDB = false): Booking
    {
        $showBookingResponse = $this->tourCMSService->showBooking($booking->getBookingId());
        $this->logger->info(["showBookingResponse" => $showBookingResponse]);
        
        // Deleted booking have no components, we must check that components exists
        $this->checkIfBookingHaveBeenDeleted($showBookingResponse, $booking->getUuid());
        $existingProduct = [];
        if($fillableFromDB) {
            $storeBookingUnitItemsArray = [];
            if (!empty($booking->complete_booking_json)) {
                $storeBooking = json_decode($booking->complete_booking_json, true);
                $storeBookingUnitItemsArray = $storeBooking['unitItems'];
                $existingProduct = $storeBooking['product'];
                $existingProduct['allDay'] = $storeBooking['availability']['allDay'] ?? false;
            }
        }
        $product = $this->productService->find($booking->product_id, $existingProduct);
        $option = $product->getOptionById($booking->option_id, $fillableFromDB);
        if ($fillableFromDB) {
            $option->setId($booking->option_id);
            foreach ($storeBookingUnitItemsArray as $unitItem) {
                try {
                    $option->getUnitById($unitItem['unitId']);
                } catch (\App\Exceptions\InvalidUnitIdException) {
                    $unit = $this->bookingBuilder->buildUnitFromJSON($unitItem['unit']);
                    $option->addUnit($unit);
                }
            }
        }

        $availability = new Availability();
        $availability = $this->availabilityService->generateAvailabilityFromBookingXML($product, $showBookingResponse);

        $booking =  BookingFactory::createFromShowBookingXML(
            $booking->getUuid(),
            $showBookingResponse, 
            $product, 
            $availability,
            $booking->unit_items,
            $option
        );
        return $booking;
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
}