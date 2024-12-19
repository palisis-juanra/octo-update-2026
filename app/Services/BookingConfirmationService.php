<?php

namespace App\Services;

use App\Exceptions\InvalidBookingUUIDException;
use App\Models\Booking;
use App\Models\Contact;

class BookingConfirmationService
{
    const ERROR_BOOKING_ALREADY_COMITTED = 'BOOKING ALREADY COMMITTED';
    const ERROR_BOOKING_NOT_FOUND = 'API return no matching data, invalid booking ID / channel';

    public function __construct(
        public TourCMSService $tourCMSService,
        public JSONLogService $logger,
        public ProductService $productService,
        public AvailabilityService $availabilityService,
        public OptionService $optionService,
        public ContactService $contactService
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

    public function confirmBooking(Booking $booking): Booking
    {
        $commitBookingResponse = $this->tourCMSService->commitBooking($booking->booking_id);
        $this->logger->info(["commitBookingResponse" => $commitBookingResponse]);
        $error = (string) $commitBookingResponse->error;

        if ($error == self::ERROR_BOOKING_ALREADY_COMITTED) {
            $this->logger->info("Booking {$booking->booking_id} already commited, calling show booking to return booking info");
            return $this->getBooking($booking);
        }

        if ($error !== TourCMSService::ERROR_OK) {
            $this->logger->error(self::ERROR_BOOKING_NOT_FOUND);
            throw new InvalidBookingUUIDException($booking->uuid);
        } 

        $booking = $this->getBooking($booking);

        return $booking;
    }

    public function getBooking(Booking $booking): Booking
    {
        $showBookingResponse = $this->tourCMSService->showBooking($booking->getBookingId());
        $this->logger->info(["showBookingResponse" => $showBookingResponse]);
        
        $product = $this->productService->find($booking->product_id);
        $option = $product->getOptionById($booking->option_id);
        $availability = $this->availabilityService->find($booking->availability_id);
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

    public function addContactToBooking(array $contactData, Booking $booking): Booking
    {
        $contact = Contact::create($contactData);
        $customerXML = $this->contactService->getCustomerXMLFromContact($booking->getLeadCustomerId(), $contact);
        $this->tourCMSService->updateCustomer($customerXML);
        $this->logger->info(["message" => "updating customer details", "details" => $customerXML]);
        
        $booking->setContact($contact);
        return $booking;
    }
}