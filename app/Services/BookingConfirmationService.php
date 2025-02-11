<?php

namespace App\Services;

use App\Exceptions\InvalidBookingUUIDException;
use App\Models\Booking;
use App\Models\Contact;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class BookingConfirmationService extends BookingService
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
        parent::__construct($tourCMSService, $logger, $productService, $availabilityService);
    }

    public function confirmBooking(Booking $booking): Booking
    {
        $commitBookingResponse = $this->tourCMSService->commitBooking($booking->booking_id, $booking->getResellerReference());
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

    public function addContactToBooking(array $contactData, Booking $booking): Booking
    {
        $contact = Contact::create($contactData);
        $customerXML = $this->contactService->getCustomerXMLFromContact($booking->getLeadCustomerId(), $contact);
        $this->tourCMSService->updateCustomer($customerXML);
        $this->logger->info(["message" => "updating customer details", "details" => $customerXML]);
        
        $booking->setContact($contact);
        return $booking;
    }

    /**
     * Unit items must remain the same, so if they are different 
     * @param \App\Models\Booking $booking
     * @param array $unitItems
     * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
     * @return void
     */
    public function checkUnitItemsHaveNotChanged(Booking $booking, array $unitItems): void
    {
        $bookingUnitItemsArray = [];
        $bookingUnitItems = json_decode($booking->unit_items, true);
        foreach ($bookingUnitItems as $unitItem) {
            $bookingUnitItemsArray[] = ["unitId" => $unitItem["unitId"]];
        }
        if ($bookingUnitItemsArray !== $unitItems) {
            $this->logger->info(["message" => "Units items has changed from reservation to confirmation, throwing exception", "reservation" => $bookingUnitItems, "confirmation" => $unitItems]);
            throw new UnprocessableEntityHttpException("Unit items must not change between reservation and confirmation");
        }
    }
}