<?php

namespace App\Services;

use App\Exceptions\NoAvailabilityException;
use App\Models\Availability\Availability;
use App\Models\Booking;
use App\Models\Option;
use App\Models\Product;
use SimpleXMLElement;

class BookingReservationService
{
    public function __construct(
        public TourCMSService $tourCMSService,
        public ProductService $productService,
        public JSONLogService $logger)
    {

    }

    public function reserve(Product $product, Option $option, Availability $availability, array $unitItems, ?string $uuid = null, string $notes = ''): Booking
    {
        $date = $availability->getDate();
        $checkAvailQueryString = $this->generateCheckAvailQueryString($date, $unitItems);

        // Make Check Avail
        $tourId = $this->productService->getTourIdFromProductId($product->getId());
        $checkAvailXML = $this->tourCMSService->checkAvailability($checkAvailQueryString, $tourId);
        $this->logger->info(["Check Avail Response" => $checkAvailXML]);

        $availableComponents = $this->tourCMSService->getArrayFromXmlNode($checkAvailXML->available_components, 'component');
        if (empty($availableComponents)) {
            $this->logger->info("No components availables for availability id {$availability->getId()}");
            throw new NoAvailabilityException;
        }

        $departureId = $availability->getAttributes()['departure_id'];
        try {
            $component = $this->getComponentByDepartureId($availableComponents, $departureId);
        } catch (NoAvailabilityException $e) {
            $this->logger->info("No component with departure ID {$departureId} found");
            throw $e;
        }
        
        $componentKey = (string) $component->component_key;

        // Start new booking with the componentId required
        $bookingData = $this->getBookingDataForStartNewBooking($unitItems, $componentKey, $uuid);
        $startNewBookingXML = $this->tourCMSService->startNewBooking($bookingData);

        // Create Booking object
        $booking = Booking::createFromXML(
            $startNewBookingXML, 
            $product, 
            $option, 
            $availability, 
            $unitItems,
            $notes);
        $this->logger->info(["message" => "Temporary booking with ID {$booking->getId()} and UUID {$booking->getUuid()} created successfully"]);
        return $booking;
    }

    public function generateRatesQueryStringFromUnitItems(array $unitItems): string
    {
        $rates = [];

        foreach ($unitItems as $unitObject) {
            $unitId = $unitObject['unitId'];
            $rateId = explode('|', $unitId)[1];
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