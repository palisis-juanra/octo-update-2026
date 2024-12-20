<?php

namespace Tests\Unit;

use App\Exceptions\BookingNotCancellableException;
use App\Exceptions\InvalidBookingUUIDException;
use App\Models\Availability\Availability;
use App\Models\Booking;
use App\Services\AvailabilityService;
use App\Services\BookingCancellationService;
use App\Services\OptionService;
use App\Services\ProductService;
use App\Services\TourCMSService;
use Tests\UnitTestCase;

class BookingCancellationServiceTest extends UnitTestCase
{
    const EXAMPLE_REASON = 'example reason';
    const VALID_BOOKING_ID = 4093;
    const VALID_BOOKING_UUID = '41cb84e7-b4d9-4cb4-809e-cac7a5e5493a';

    public function test_whenCheckingIfNonCancellableBookingIsCancellable_thenThrowsBookingNotCancellableException(): void
    {
        // Given
        $tourCMSServiceMock = $this->getMockBuilder(TourCMSService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $bookingCancellationService = $this->getMockBuilder(BookingCancellationService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $bookingCancellationService->tourCMSService = $tourCMSServiceMock;
        $bookingCancellationService->logger = $this->getLoggerMock();

        $booking = new Booking();
        $booking->setCancellable(0);

        // When
        $this->expectException(BookingNotCancellableException::class);

        $bookingCancellationService->checkIfBookingIsCancellable($booking);
    }

    public function test_whenCancelBookingAPIResponseNotOk_thenThrowsInvalidUUIDException(): void
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

        $booking = new Booking();
        $booking->booking_id = self::VALID_BOOKING_ID;
        $booking->uuid = self::VALID_BOOKING_UUID;
        $reason = self::EXAMPLE_REASON;

        // When
        $this->expectException(InvalidBookingUUIDException::class);

        $bookingCancellationService->cancelBooking($booking, $reason);
    }
}