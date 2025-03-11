<?php

namespace App\Factories;

use App\Facades\JSONLog;
use App\Models\Availability\Availability;
use App\Models\Booking;
use App\Models\BookingCancellation;
use App\Models\Contact;
use App\Models\Option;
use App\Models\Product;
use App\Models\Voucher;
use App\Models\UnitItem;
use App\Services\XMLService;
use SimpleXMLElement;

class BookingFactory
{
    /**
     * Summary of createFromStartNewBookingXML
     * @param SimpleXMLElement $startNewBookingData
     * @param Product $product
     * @param Option $option
     * @param Availability $availability
     * @param array $unitItems
     * @param ?string $notes
     * @return Booking
     */
    public static function createFromStartNewBookingXML(
        SimpleXMLElement $startNewBookingData,
        Product $product,
        Option $option,
        Availability $availability,
        array $requestUnitItems,
        ?string $notes = ''
    ): Booking
    {
        $booking = new Booking();

        $bookingData = $startNewBookingData->booking;
        
        $booking->setBookingData($bookingData);
        $booking->setBookingId((int) $bookingData->booking_id);
        $booking->setUuid((string) $bookingData->booking_uuid);
        $booking->setAccountId((int) $bookingData->account_id);
        $booking->setChannelId((int) $bookingData->channel_id);
        $booking->setUtcExpiresAt((int) $bookingData->hold_time_seconds);
        $booking->setExpirationMinutes((int) $bookingData->hold_time_seconds / 60);
        $booking->setProduct($product);
        $booking->setOption($option);
        $booking->setAvailability($availability);


        $unitsCount = [];

        foreach ($requestUnitItems as $unitItem) {
            if (array_key_exists($unitItem['unitId'], $unitsCount)) {
                $unitsCount[$unitItem['unitId']]++;
            } else {
                $unitsCount[$unitItem['unitId']] = 1;
            }

            $unit = $option->getUnitById($unitItem['unitId']);
            $unitItems[] = UnitItemFactory::create($booking, $unit, $unitsCount[$unitItem['unitId']]);
        }

        $booking->setUnits($unitItems);
        
        if (!is_null($notes)){
            $booking->setNotes($notes);
        }

        if (in_array(Booking::FIELD_VOUCHER, $product->getDeliveryMethods())) {
            $voucher = Voucher::createWithoutOptions($product->getRedemptionMethod());
            $booking->setVoucher($voucher);
        }

        return $booking;
    }

    /**
     * Get the booking informatin from tourcms show booking response
     * @param string $bookingUuid
     * @param \SimpleXMLElement $showBookingXML
     * @param \App\Models\Product $product
     * @param \App\Models\Option $option
     * @param \App\Models\Availability\Availability $availability
     * @param string $savedUnitItems we need to pass already stored unit items to know the uuid of each one
     * @return Booking
     */
    public static function createFromShowBookingXML(
        string $bookingUuid,
        SimpleXMLElement $showBookingXML,
        Product $product,
        Option $option,
        Availability $availability,
        string $savedUnitItems
    ): Booking
    {
        $booking = new Booking();

        $bookingData = $showBookingXML->booking;
        
        $booking->setBookingData($bookingData);
        $booking->setBookingId((int) $bookingData->booking_id);
        $booking->setUuid((string) $bookingUuid);
        $booking->setAccountId((int) $bookingData->account_id);
        $booking->setChannelId((int) $bookingData->channel_id);

        $booking->setLeadCustomerId((int) $bookingData->lead_customer_id);
        if (isset($bookingData->agent_ref) && !empty((string)$bookingData->agent_ref)) {
            $booking->setResellerReference((string) $bookingData->agent_ref);
        }

        $contact = Contact::createContactArrayFromXML($bookingData);
        $booking->setContact($contact);

        $booking->setUtcCreatedAt((int) $bookingData->made_date_time_at_utc_seconds);
        $booking->setUtcExpiresAt(isset($bookingData->expiry_date_at_utc_seconds) ? (int) $bookingData->expiry_date_at_utc_seconds : null);
        $booking->setUtcRedeemedAt(Booking::getFirstRedeemed($bookingData));

        $booking->setExpirationMinutes(null);

        $status = self::getBookingStatus($bookingData);
        $booking->setStatus($status);
        if ($status == Booking::STATUS_CONFIRMED) {
            $booking->setUtcConfirmedAt(isset($bookingData->confirmed_at_utc_seconds) ? (int) $bookingData->confirmed_at_utc_seconds : null);
        }
        $booking->setCancellable((bool) $bookingData->cancellable);

        if ((int) $bookingData->cancel_reason !== 0) {
            $cancellation = new BookingCancellation(
                (string) $bookingData->cancel_text,
                Booking::createUtcCancelledAt((int) $bookingData->cancelled_at_utc_seconds),
            );
            $booking->setCancellation(cancellation: $cancellation);
        }
        
        $booking->setProduct($product);
        $booking->setOption($option);
        $booking->setAvailability(availability: $availability);

        // UNITS
        $customers = XMLService::getArrayFromXmlNode($bookingData->customers, 'customer');
        JSONLog::info(["booking" => print_r($booking, 1)]);

        $unitItems = [];
        $unitsQuantities = [];

        $storedUnitItems = json_decode($savedUnitItems, 1);
        foreach ($storedUnitItems as $emptyUnitItem) {
            
            $unitId = (string) $emptyUnitItem['unitId'];
            $unit = $option->getUnitById($unitId);
            
            if (array_key_exists($unitId, $unitsQuantities)) {
                $unitsQuantities[$unitId]++;
            } else {
                $unitsQuantities[$unitId] = 1;
            }

            $unitItem = UnitItemFactory::create($booking, $unit, $unitsQuantities[$unitId]);
            
            $unitItems[] = $unitItem;
        }

        $booking->setUnits($unitItems);
        
        if (in_array(Booking::FIELD_VOUCHER, $product->getDeliveryMethods())) {
            $voucher = Voucher::create($bookingData, $product->getRedemptionMethod(), $booking->getUtcRedeemedAt());
            $booking->setVoucher($voucher);
        }

        return $booking;
    }

    public static function getBookingStatus(SimpleXMLElement $bookingData): string
    {
        if ((string) $bookingData->status == Booking::TCMS_STATUS_TEMPORARY) {
            return Booking::STATUS_ON_HOLD;
        }

        if ((int) $bookingData->cancel_reason !== 0) {
            return Booking::STATUS_CANCELLED;
        }

        if (self::isBookingRedeemed(XMLService::getArrayFromXmlNode($bookingData->components, 'component'))) {
            return Booking::STATUS_REDEEMED;
        }

        return (int) $bookingData->status == Booking::TCMS_STATUS_CONFIRMED ? Booking::STATUS_CONFIRMED : Booking::STATUS_PENDING;
    }

    public static function isBookingRedeemed(array $components): bool
    {
        foreach ($components as $component) {
            if (!empty($component->redeemed_at) || !empty($component->redeemed_at_utc_seconds)) {
                return true;
            }
        }

        return false;
    }

}