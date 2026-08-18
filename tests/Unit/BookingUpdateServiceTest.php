<?php

namespace Tests\Unit;

use App\Exceptions\BookingNotCancellableException;
use App\Exceptions\InvalidBookingUUIDException;
use App\Models\Booking;
use App\Services\BookingCancellationService;
use App\Services\BookingUpdateService;
use App\Services\TourCMSService;
use SimpleXMLElement;
use Tests\UnitTestCase;

class BookingUpdateServiceTest extends UnitTestCase
{
    const OLD_BOOKING_UUID = '41cb84e7-b4d9-4cb4-809e-cac7a5e5493a';
    const NEW_UUID = '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d';
    const OLD_TCMS_BOOKING_ID = 4093;
    const NEW_TCMS_BOOKING_ID = 3889;
    const CHANNEL_ID = 142;

    public function test_whenOldBookingIsNotCancellable_thenThrowsBookingNotCancellableException(): void
    {
        $oldBookingByUuid = new Booking();
        $oldBookingByUuid->setUuid(self::OLD_BOOKING_UUID);
        $oldBookingByUuid->setBookingId(self::OLD_TCMS_BOOKING_ID);

        $oldBooking = new Booking();
        $oldBooking->setUuid(self::OLD_BOOKING_UUID);
        $oldBooking->setBookingId(self::OLD_TCMS_BOOKING_ID);
        $oldBooking->setCancellable(false);

        $bookingCancellationServiceMock = $this->getMockBuilder(BookingCancellationService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBookingByUuid', 'getBooking'])
            ->getMock();

        $bookingCancellationServiceMock
            ->method('getBookingByUuid')
            ->willReturn($oldBookingByUuid);

        $bookingCancellationServiceMock
            ->method('getBooking')
            ->willReturn($oldBooking);

        $bookingUpdateService = $this->getMockBuilder(BookingUpdateService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $bookingUpdateService->bookingCancellationService = $bookingCancellationServiceMock;
        $bookingUpdateService->logger = $this->getLoggerMock();

        $this->expectException(BookingNotCancellableException::class);

        $bookingUpdateService->update(self::OLD_BOOKING_UUID, []);
    }

    public function test_whenOldBookingUuidDoesNotExist_thenThrowsInvalidBookingUuidException(): void
    {
        $bookingCancellationServiceMock = $this->getMockBuilder(BookingCancellationService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBookingByUuid'])
            ->getMock();

        $bookingCancellationServiceMock
            ->method('getBookingByUuid')
            ->willThrowException(new InvalidBookingUUIDException('unknown-uuid'));

        $bookingUpdateService = $this->getMockBuilder(BookingUpdateService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $bookingUpdateService->bookingCancellationService = $bookingCancellationServiceMock;
        $bookingUpdateService->logger = $this->getLoggerMock();

        $this->expectException(InvalidBookingUUIDException::class);

        $bookingUpdateService->update('unknown-uuid', []);
    }

    public function test_whenReassigningBookingUuid_thenCallsUpdateBookingWithBookingIdAndNewUuid(): void
    {
        $booking = new Booking();
        $booking->setBookingId(self::OLD_TCMS_BOOKING_ID);

        $tourCMSServiceMock = $this->getMockBuilder(TourCMSService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['updateBooking'])
            ->getMock();

        $tourCMSServiceMock
            ->expects($this->once())
            ->method('updateBooking')
            ->with($this->callback(function (SimpleXMLElement $bookingData) {
                return (string) $bookingData->booking_id === (string) self::OLD_TCMS_BOOKING_ID
                    && (string) $bookingData->booking_uuid === self::NEW_UUID;
            }))
            ->willReturn(simplexml_load_string('<response><error>OK</error></response>'));

        $bookingUpdateService = $this->getMockBuilder(BookingUpdateService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $bookingUpdateService->tourCMSService = $tourCMSServiceMock;

        $method = self::getProtectedMethod($bookingUpdateService, 'reassignBookingUuid');
        $method->invoke($bookingUpdateService, $booking, self::NEW_UUID);
    }

    public function test_whenCreatingBookingReplacedAuditNote_thenNoteMentionsNewBookingTourCMSId(): void
    {
        $oldBooking = new Booking();
        $oldBooking->setBookingId(self::OLD_TCMS_BOOKING_ID);
        $oldBooking->setChannelId(self::CHANNEL_ID);

        $newBooking = new Booking();
        $newBooking->setBookingId(self::NEW_TCMS_BOOKING_ID);

        $tourCMSServiceMock = $this->getMockBuilder(TourCMSService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['callTourCMSAddNoteToBooking'])
            ->getMock();

        $tourCMSServiceMock
            ->expects($this->once())
            ->method('callTourCMSAddNoteToBooking')
            ->with(
                self::CHANNEL_ID,
                self::OLD_TCMS_BOOKING_ID,
                $this->stringContains((string) self::NEW_TCMS_BOOKING_ID)
            );

        $bookingUpdateService = $this->getMockBuilder(BookingUpdateService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $bookingUpdateService->tourCMSService = $tourCMSServiceMock;

        $method = self::getProtectedMethod($bookingUpdateService, 'createBookingReplacedAuditNote');
        $method->invoke($bookingUpdateService, $oldBooking, $newBooking);
    }

    public function test_whenCreatingBookingRebookAuditNote_thenNoteMentionsOldBookingTourCMSIdAndUuid(): void
    {
        $oldBooking = new Booking();
        $oldBooking->setBookingId(self::OLD_TCMS_BOOKING_ID);

        $newBooking = new Booking();
        $newBooking->setBookingId(self::NEW_TCMS_BOOKING_ID);
        $newBooking->setChannelId(self::CHANNEL_ID);

        $tourCMSServiceMock = $this->getMockBuilder(TourCMSService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['callTourCMSAddNoteToBooking'])
            ->getMock();

        $tourCMSServiceMock
            ->expects($this->once())
            ->method('callTourCMSAddNoteToBooking')
            ->with(
                self::CHANNEL_ID,
                self::NEW_TCMS_BOOKING_ID,
                $this->logicalAnd(
                    $this->stringContains((string) self::OLD_TCMS_BOOKING_ID),
                    $this->stringContains(self::OLD_BOOKING_UUID)
                )
            );

        $bookingUpdateService = $this->getMockBuilder(BookingUpdateService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $bookingUpdateService->tourCMSService = $tourCMSServiceMock;

        $method = self::getProtectedMethod($bookingUpdateService, 'createBookingRebookAuditNote');
        $method->invoke($bookingUpdateService, $newBooking, $oldBooking, self::OLD_BOOKING_UUID);
    }
}
