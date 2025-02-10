<?php

namespace App\Factories;

use App\Models\Availability\Availability;
use App\Models\Booking;
use App\Models\BookingCancellation;
use App\Models\Contact;
use App\Models\Option;
use App\Models\Product;
use App\Models\Voucher;
use SimpleXMLElement;

class BookingFactory
{
    public static function createFromStartNewBookingXML(
        SimpleXMLElement $startNewBookingData,
        Product $product,
        Option $option,
        Availability $availability,
        array $unitItems,
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

    public static function createFromShowBookingXML(
        string $bookingUuid,
        SimpleXMLElement $showBookingXML,
        Product $product,
        Option $option,
        Availability $availability,
        array $unitItems,
        Contact $contact
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
        $booking->setContact($contact);
        $booking->setUtcCreatedAt((int) $bookingData->made_date_time_at_utc_seconds);
        $booking->setUtcExpiresAt(isset($bookingData->expiry_date_at_utc_seconds) ? (int) $bookingData->expiry_date_at_utc_seconds : null);
        $booking->setUtcRedeemedAt(Booking::getFirstRedeemed($bookingData));

        $booking->setExpirationMinutes(null);

        $status = Booking::getBookingStatus($bookingData);
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
        $booking->setAvailability($availability);
        $booking->setUnits($unitItems);
        
        if (in_array(Booking::FIELD_VOUCHER, $product->getDeliveryMethods())) {
            $voucher = Voucher::create($bookingData, $product->getRedemptionMethod(), $booking->getUtcRedeemedAt());
            $booking->setVoucher($voucher);
        }

        return $booking;
    }
}