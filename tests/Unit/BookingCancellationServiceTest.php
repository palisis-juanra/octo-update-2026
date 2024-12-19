<?php

namespace Tests\Unit;

use App\Exceptions\BookingNotCancellableException;
use App\Models\Booking;
use App\Services\BookingCancellationService;
use App\Services\TourCMSService;
use Tests\UnitTestCase;

class BookingCancellationServiceTest extends UnitTestCase
{
    const EXAMPLE_REASON = 'example reason';

    public function test_whenCancellingNonCancellableBooking_thenThrowsBookingNotCancellableException(): void
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
        $reason = self::EXAMPLE_REASON;

        // When
        $this->expectException(BookingNotCancellableException::class);

        $bookingCancellationService->cancelBooking($booking, $reason);
    }
}