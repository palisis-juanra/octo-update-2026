<?php

namespace Tests\Unit;

use App\Builders\BookingBuilder;
use App\Builders\BookingChecker;
use App\Exceptions\InvalidBookingUUIDException;
use App\Models\Booking;
use App\Services\AvailabilityService;
use App\Services\BookingCancellationService;
use App\Services\JSONLogService;
use App\Services\ProductService;
use App\Services\TourCMSService;
use Tests\UnitTestCase;

class BookingCancellationServiceTest extends UnitTestCase
{
    const EXAMPLE_REASON = 'example reason';

    const VALID_BOOKING_ID = 4093;

    const VALID_BOOKING_UUID = '41cb84e7-b4d9-4cb4-809e-cac7a5e5493a';

    public function test_when_cancel_booking_api_response_is_not_valid_then_throws_invalid_uuid_exception(): void
    {
        // Given
        $tourCMSServiceMock = $this->getMockBuilder(TourCMSService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['cancelBooking'])
            ->getMock();

        $bookingCancellationService = $this->getMockBuilder(BookingCancellationService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCancelBookingXMLRequest'])
            ->getMock();
        $bookingCancellationService->tourCMSService = $tourCMSServiceMock;
        $bookingCancellationService->logger = $this->getLoggerMock();

        $getCancelBookingXMLRequestResponse = simplexml_load_string('<booking><booking_id>123</booking_id><note>note</note></booking>');
        $cancelBookingResponse = simplexml_load_string('<response><request>POST /c/booking/cancel.xml</request><error>INVALID BOOKING ID</error></response>');

        $tourCMSServiceMock
            ->method('cancelBooking')
            ->willReturn($cancelBookingResponse);

        $bookingCancellationService
            ->method('getCancelBookingXMLRequest')
            ->willReturn($getCancelBookingXMLRequestResponse);

        $booking = new Booking;
        $booking->booking_id = self::VALID_BOOKING_ID;
        $booking->uuid = self::VALID_BOOKING_UUID;
        $booking->status = Booking::STATUS_CONFIRMED;
        $reason = self::EXAMPLE_REASON;

        // When
        $this->expectException(InvalidBookingUUIDException::class);

        $bookingCancellationService->cancelBooking($booking, $reason);
    }

    public function test_when_calling_show_booking_and_already_redeemed_then_throws_booking_already_redeemed_exception(): void
    {
        // Given
        $bookingCancellationService = new BookingCancellationService(
            $this->getMockBuilder(TourCMSService::class)
                ->disableOriginalConstructor()
                ->getMock(),
            $this->getMockBuilder(ProductService::class)
                ->disableOriginalConstructor()
                ->getMock(),
            $this->getMockBuilder(AvailabilityService::class)
                ->disableOriginalConstructor()
                ->getMock(),
            $this->getMockBuilder(JSONLogService::class)
                ->disableOriginalConstructor()
                ->getMock(),
            $this->getMockBuilder(BookingBuilder::class)
                ->disableOriginalConstructor()
                ->getMock(),
            $this->getMockBuilder(BookingChecker::class)
                ->disableOriginalConstructor()
                ->getMock()

        );

        $booking = new Booking;
        $booking->status = Booking::STATUS_REDEEMED;

        // When
        $this->expectException(\App\Exceptions\BookingAlreadyRedeemedException::class);

        $bookingCancellationService->shouldWeCancelBooking($booking);
    }
}
