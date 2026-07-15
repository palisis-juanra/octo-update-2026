<?php

namespace App\Services;

use App\Builders\BookingBuilder;
use App\Builders\BookingChecker;
use App\Exceptions\InvalidBookingUUIDException;
use App\Models\Booking;
use App\Models\Contact;
use App\Exceptions\FailPermissionException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class BookingConfirmationService extends BookingService
{
    public const ERROR_BOOKING_ALREADY_COMITTED = 'BOOKING ALREADY COMMITTED';
    public const ERROR_BOOKING_NOT_FOUND = 'API return no matching data, invalid booking ID / channel';
    public const ERROR_MESSAGE_UNIT_ITEMS_CHANGED = "Unit items must not change between reservation and confirmation";

    public function __construct(
        public TourCMSService $tourCMSService,
        public JSONLogService $logger,
        public ProductService $productService,
        public AvailabilityService $availabilityService,
        public OptionService $optionService,
        public ContactService $contactService,
        public BookingBuilder $bookingBuilder,
        public BookingChecker $checker
    )
    {
        parent::__construct($tourCMSService, $logger, $productService, $availabilityService, $bookingBuilder, $checker);
    }

    /**
     * Call TourCMS to confirm booking
     * @param \App\Models\Booking $booking
     * @throws \App\Exceptions\InvalidBookingUUIDException
     * @return Booking
     */
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

    /**
     * Update customer information in TourCMS
     * @param int $customerId TourCMS customer ID
     * @param \App\Models\Contact $contact
     * @return bool
     */
    public function updateTraveller(int $customerId, Contact $contact): bool
    {
        try {
            $customerXML = $this->contactService->getCustomerXMLFromContact($customerId, $contact);
            $this->tourCMSService->updateCustomer($customerXML);
            return true;
        } catch (FailPermissionException $e) {
            $this->logger->error(["message" => "Error updating customer {$customerId}"]);
            $this->logger->error($e);
            return false;
        }
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
            $bookingUnitItemsArray[] = $unitItem["unitId"];
        }

        $unitIds = array_column($unitItems, "unitId");

        sort($bookingUnitItemsArray);
        sort($unitIds);

        if ($bookingUnitItemsArray !== $unitIds) {
            $this->logger->info(["message" => "Units items has changed from reservation to confirmation, throwing exception", "reservation" => $bookingUnitItems, "confirmation" => $unitItems]);
            throw new UnprocessableEntityHttpException(self::ERROR_MESSAGE_UNIT_ITEMS_CHANGED);
        }
    }
}