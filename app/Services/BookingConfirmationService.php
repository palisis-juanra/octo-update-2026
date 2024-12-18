<?php

namespace App\Services;

use App\Exceptions\APICallNotOKException;
use App\Exceptions\InvalidBookingUUIDException;
use App\Exceptions\NoMatchingDataException;
use App\Models\Booking;
use App\Models\Contact;

class BookingConfirmationService
{
    const ERROR_BOOKING_ALREADY_COMITTED = 'BOOKING ALREADY COMMITTED';

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
            return $this->getBooking($booking);
        }

        if ($error !== TourCMSService::ERROR_OK) {
            $this->logger->error('API return no matching data, invalid booking ID / channel');
            throw new InvalidBookingUUIDException($booking->uuid);
        } 

        $booking = $this->getBooking($booking);
        $booking->setUtcConfirmedAt();

        return $booking;
    }

    public function getBooking(Booking $booking): Booking
    {
        $showBookingResponse = $this->tourCMSService->showBooking($booking->getBookingId());
        
        $product = $this->productService->find($booking->product_id);
        $option = $product->getOptionById($booking->option_id);
        $availability = $this->availabilityService->find(availabilityId: $booking->availability_id);
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
        $this->tourCMSService->updateCustomer($booking->getLeadCustomerId(), $customerXML);
        
        $booking->setContact($contact);
        return $booking;
    }
}