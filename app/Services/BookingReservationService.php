<?php

namespace App\Services;

use App\Exceptions\NoAvailabilityException;
use App\Factories\BookingFactory;
use App\Factories\UnitItemFactory;
use App\Models\Availability\Availability;
use App\Models\Booking;
use App\Models\Option;
use App\Models\Product;
use App\Services\AvailabilityService;
use SimpleXMLElement;

class BookingReservationService
{
    public function __construct(
        public TourCMSService $tourCMSService,
        public ProductService $productService,
        public AvailabilityService $availabilityService,
        public JSONLogService $logger,
    ) {}

    public function reserve(Product $product, Option $option, Availability $availability, array $unitItems, ?string $uuid = null, string $notes = ''): Booking
    {
        $date = $availability->getDate();
        $departureId = $availability->getAttributes()['departure_id'];
        $availabilityId = $availability->getId();

        $checkAvailQueryString = $this->generateCheckAvailQueryString($date, unitItems: $unitItems);

        // Make Check Avail
        $tourId = $this->productService->getTourIdFromProductId($product->getId());
        $checkAvailXML = $this->tourCMSService->checkAvailability($checkAvailQueryString, $tourId);
        $this->logger->info(["Check Avail Response" => $checkAvailXML]);

        $availableComponents = $this->tourCMSService->getArrayFromXmlNode($checkAvailXML->available_components, 'component');
        if (empty($availableComponents)) {
            $this->logger->info("No components availables for availability id {$availabilityId}");
            throw new NoAvailabilityException;
        }

        try {
            $component = $this->getComponentByDepartureId($availableComponents, $departureId);
        } catch (NoAvailabilityException $e) {
            $this->logger->info("No component with departure ID {$departureId} found");
            throw $e;
        }
        
        $componentKey = (string) $component->component_key;
        $component->addChild('availability_type', $product->getAvailabilityType());
        $availabilityFromComponent = $this->availabilityService->generateAvailabilityObjectFromComponent($component);

        // Start new booking with the componentId required
        $bookingData = $this->getBookingDataForStartNewBooking($unitItems, $componentKey, $uuid);
        $startNewBookingXML = $this->tourCMSService->startNewBooking($bookingData);
        $this->logger->info(["message" => "Temporary booking created in TourCMS"]);

        // Create Booking object
        $booking = BookingFactory::createFromStartNewBookingXML(
            $startNewBookingXML, 
            $product, 
            $option, 
            $availabilityFromComponent, 
            $unitItems,
            $notes);
        $booking->save();
        $this->logger->info(["message" => "Temporary booking persisted with ID {$booking->getId()} and UUID {$booking->getUuid()}"]);
        return $booking;
    }

    public function generateRatesQueryStringFromUnitItems(array $unitItems): string
    {
        $rates = [];

        foreach ($unitItems as $unitObject) {
            
            $unitId = $unitObject[UnitService::UNIT_ID_FIELD];
            $rateId = UnitService::getTourCMSRateId($unitId);

            if (!array_key_exists($rateId, $rates)) {
                $rates[$rateId] = 1;
            } else {
                $rates[$rateId]++;
            }
        }

        $rateQueryString = '';
        foreach ($rates as $rateId => $quantity) {
            $rateQueryString .= "{$rateId}={$quantity}&";
        }
        $rateQueryString = substr($rateQueryString, 0, -1);

        return $rateQueryString;
    }

    public function generateCheckAvailQueryString(string $date, array $unitItems): string
    {
        $dateQueryString = "date={$date}";
        $ratesQueryString = $this->generateRatesQueryStringFromUnitItems($unitItems);
        return "{$dateQueryString}&{$ratesQueryString}";
    }

    /**
     * @param array $components
     * @param string $departureId
     * @throws NoAvailabilityException
     * @return SimpleXMLElement component with desired departure id
     */
    public function getComponentByDepartureId(array $components, string $departureId): SimpleXMLElement
    {
        foreach ($components as $component) {
            if ($component->date_id == $departureId){
                return $component;
            }
        }

        throw new NoAvailabilityException;
    }

    public function getBookingDataForStartNewBooking(array $unitItems, string $componentKey, string $uuid = null): SimpleXMLElement
    {
        $booking = new SimpleXMLElement('<booking />');
        $booking->addChild('manage_uuid', 1);
        if (!is_null($uuid)) {
            $booking->addChild('booking_uuid', $uuid);
        }
        $booking->addChild('total_customers', count($unitItems));
        $components = $booking->addChild('components');
        $component = $components->addChild('component');
        $component->addChild('component_key', $componentKey);
        $booking->addChild('customers');

        return $booking;
    }

}