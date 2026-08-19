<?php

namespace Tests\Feature;

use App\Exceptions\InvalidBookingUUIDException;
use App\Http\Responses\OctoResponse;
use App\Mail\BookingCancellationFailedMail;
use App\Models\Availability\Availability;
use App\Models\Booking;
use App\Models\Contact;
use App\Services\AvailabilityService;
use App\Services\BookingCancellationService;
use App\Services\BookingConfirmationService;
use App\Services\BookingContactService;
use App\Services\BookingReservationService;
use App\Services\BookingUpdateService;
use App\Services\ContactService;
use App\Services\LocaleService;
use App\Services\OptionService;
use App\Services\ProductMappingFactory;
use App\Services\ProductService;
use App\Services\TourCMSService;
use App\Services\TourPromotionService;
use App\Services\UnitService;
use App\Transformers\BaseTransformer;
use App\Transformers\BookingTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\MockObject\MockObject;
use SimpleXMLElement;
use Tests\FeatureTestCase;

class BookingUpdateTest extends FeatureTestCase
{
    use RefreshDatabase;

    const INVALID_BOOKING_UUID = 'invalidUuid';

    const CONFIRMED_BOOKING_UUID = '41cb84e7-b4d9-4cb4-809e-cac7a5e5493a';

    const ON_HOLD_BOOKING_UUID = 'fd99cf42-ff76-4152-a31d-1a6f7efb26f3';

    const NOT_CANCELLABLE_BOOKING_UUID = '3f3a7973-fcaa-44f4-a934-e4c252092436';

    const CANCEL_FAILS_BOOKING_UUID = '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d';

    const CONFIRMED_TCMS_BOOKING_ID = 4093;

    const ON_HOLD_TCMS_BOOKING_ID = 3899;

    const NOT_CANCELLABLE_TCMS_BOOKING_ID = 4094;

    const CANCEL_FAILS_TCMS_BOOKING_ID = 4095;

    const VALID_PRODUCT_ID = 'TE_1_67|142';

    const VALID_AVAILABILITY_ID = '2024-12-05|32293';

    const VALID_OPTION_ID = 'START_TIME';

    const VALID_UNIT_ITEMS = [
        ['unitId' => 'TE_1_67|142|r1'],
        ['unitId' => 'TE_1_67|142|r2'],
    ];

    public MockObject $tourCMSServiceMock;

    public MockObject $bookingCancellationServiceMock;

    public ProductService $productService;

    public BookingUpdateService $bookingUpdateService;

    public function test_when_booking_uuid_is_invalid_then_expects_invalid_booking_uuid_error(): void
    {
        $this->mockServices();

        $response = $this->callEndpoint(self::INVALID_BOOKING_UUID, []);

        $response->assertBadRequest();
        $responseData = $response->decodeResponseJson();
        $this->assertEquals($responseData['error'], OctoResponse::ERROR_CODE_INVALID_BOOKING_UUID);
        $this->assertEquals($responseData['errorMessage'], OctoResponse::ERROR_MESSAGE_INVALID_BOOKING_UUID);
    }

    public function test_when_booking_is_not_cancellable_then_expects_booking_not_cancellable_error(): void
    {
        $this->mockServices();

        $response = $this->callEndpoint(self::NOT_CANCELLABLE_BOOKING_UUID, []);

        $response->assertBadRequest();
        $responseData = $response->decodeResponseJson();
        $this->assertEquals($responseData['error'], OctoResponse::ERROR_CODE_UNPROCESSABLE_ENTITY);
    }

    public function test_when_old_booking_is_on_hold_then_new_booking_keeps_same_uuid_and_stays_on_hold(): void
    {
        $this->mockServices();

        $response = $this->callEndpoint(self::ON_HOLD_BOOKING_UUID, [
            'optionId' => self::VALID_OPTION_ID,
            'availabilityId' => self::VALID_AVAILABILITY_ID,
            'unitItems' => self::VALID_UNIT_ITEMS,
        ]);

        $response->assertOk();
        $responseData = $response->decodeResponseJson();
        $responseData->assertFragment([
            'uuid' => self::ON_HOLD_BOOKING_UUID,
            'status' => Booking::STATUS_ON_HOLD,
            'productId' => self::VALID_PRODUCT_ID,
            'optionId' => self::VALID_OPTION_ID,
        ]);
    }

    public function test_when_old_booking_is_confirmed_then_new_booking_keeps_same_uuid_and_is_confirmed(): void
    {
        $this->mockServices();

        $response = $this->callEndpoint(self::CONFIRMED_BOOKING_UUID, [
            'optionId' => self::VALID_OPTION_ID,
            'availabilityId' => self::VALID_AVAILABILITY_ID,
            'unitItems' => self::VALID_UNIT_ITEMS,
        ]);

        $response->assertOk();
        $responseData = $response->decodeResponseJson();
        $responseData->assertFragment([
            'uuid' => self::CONFIRMED_BOOKING_UUID,
            'status' => Booking::STATUS_CONFIRMED,
            'productId' => self::VALID_PRODUCT_ID,
            'optionId' => self::VALID_OPTION_ID,
        ]);
    }

    public function test_when_old_booking_cancellation_fails_then_sends_notification_email_and_still_returns_new_booking(): void
    {
        Mail::fake();
        $this->mockServices(cancelFails: true);

        $response = $this->callEndpoint(self::CANCEL_FAILS_BOOKING_UUID, [
            'optionId' => self::VALID_OPTION_ID,
            'availabilityId' => self::VALID_AVAILABILITY_ID,
            'unitItems' => self::VALID_UNIT_ITEMS,
        ]);

        $response->assertOk();
        $responseData = $response->decodeResponseJson();
        $responseData->assertFragment([
            'uuid' => self::CANCEL_FAILS_BOOKING_UUID,
        ]);

        Mail::assertSent(BookingCancellationFailedMail::class);
    }

    protected function callEndpoint(string $uuid, array $body)
    {
        return $this->patch("/bookings/{$uuid}", $body, [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]);
    }

    protected function mockServices(bool $cancelFails = false): void
    {
        Mail::fake();
        $showChannelXML = simplexml_load_file('tests/TourCMSResponses/showChannel.xml');
        $showTourXML = simplexml_load_file('tests/TourCMSResponses/showTour_67.xml');
        $checkAvailXML = simplexml_load_file('tests/TourCMSResponses/checkAvailability.xml');
        $startNewBookingXML = simplexml_load_file('tests/TourCMSResponses/startNewBooking.xml');
        $showBookingXML = simplexml_load_file('tests/TourCMSResponses/showBooking.xml');
        $commitBookingXML = simplexml_load_file('tests/TourCMSResponses/commitBooking.xml');
        $deleteBookingXML = simplexml_load_file('tests/TourCMSResponses/deleteBooking.xml');
        $cancelBookingOkXML = simplexml_load_string('<response><request>POST /c/booking/cancel.xml</request><error>OK</error></response>');
        $cancelBookingFailXML = simplexml_load_string('<response><request>POST /c/booking/cancel.xml</request><error>INVALID BOOKING ID</error></response>');
        $updateBookingOkXML = simplexml_load_string('<response><request>POST /c/booking/update.xml</request><error>OK</error></response>');

        $expectedTourId = '67';
        $expectedChannelId = '142';

        $this->tourCMSServiceMock = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods([
                'showChannel', 'showTour', 'checkAvailability', 'startNewBooking',
                'showBooking', 'commitBooking', 'cancelBooking', 'deleteBooking',
                'updateBooking', 'callTourCMSAddNoteToBooking', 'updateCustomer',
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->tourCMSServiceMock->method('showChannel')->willReturn($showChannelXML);
        $this->tourCMSServiceMock->method('showTour')->with($expectedTourId, $expectedChannelId)->willReturn($showTourXML);
        $this->tourCMSServiceMock->method('checkAvailability')->willReturn($checkAvailXML);

        $this->tourCMSServiceMock
            ->method('startNewBooking')
            ->willReturnCallback(function (SimpleXMLElement $bookingData) use ($startNewBookingXML) {
                $response = simplexml_load_string($startNewBookingXML->asXML());
                $response->booking->booking_uuid = (string) $bookingData->booking_uuid;

                return $response;
            });

        $this->tourCMSServiceMock->method('showBooking')->willReturn($showBookingXML);
        $this->tourCMSServiceMock->method('commitBooking')->willReturn($commitBookingXML);
        $this->tourCMSServiceMock->method('deleteBooking')->willReturn($deleteBookingXML);
        $this->tourCMSServiceMock->method('updateBooking')->willReturn($updateBookingOkXML);
        $this->tourCMSServiceMock->method('updateCustomer')->willReturnCallback(fn ($customer) => $customer);

        $this->tourCMSServiceMock
            ->method('cancelBooking')
            ->willReturn($cancelFails ? $cancelBookingFailXML : $cancelBookingOkXML);

        $this->instance(TourCMSService::class, $this->tourCMSServiceMock);

        $tourPromotionServiceMock = $this->getMockBuilder(TourPromotionService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productService = new ProductService($this->tourCMSServiceMock, $this->getLoggerMock(), new LocaleService, new ProductMappingFactory, $tourPromotionServiceMock);
        $this->instance(ProductService::class, $this->productService);

        $availabilityService = $this->getMockBuilder(AvailabilityService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $availabilityService->tourCMSService = $this->tourCMSServiceMock;
        $this->instance(AvailabilityService::class, $availabilityService);

        $product = $this->productService->find(self::VALID_PRODUCT_ID);
        $option = $product->getOptionById(self::VALID_OPTION_ID);

        $availability = new Availability;
        $availability->setId(self::VALID_AVAILABILITY_ID);
        $availability->setLocalDateTimeStart('2024-12-05');
        $availability->setLocalDateTimeEnd('2024-12-05');
        $availability->setDepartureId(32293);
        $availability->setAllDay(false);
        $availability->setOpeningHoursFrom('00:00');
        $availability->setOpeningHoursTo('23:00');

        $bookingReservationService = new BookingReservationService($this->tourCMSServiceMock, $this->productService, $availabilityService, $this->getLoggerMock());

        $bookingConfirmationService = $this->getMockBuilder(BookingConfirmationService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $bookingConfirmationService->tourCMSService = $this->tourCMSServiceMock;
        $bookingConfirmationService->logger = $this->getLoggerMock();
        $bookingConfirmationService->productService = $this->productService;
        $bookingConfirmationService->availabilityService = $availabilityService;
        $bookingConfirmationService->contactService = new ContactService;

        $bookingContactService = new BookingContactService(new ContactService, $bookingConfirmationService);

        // uuid => [tcmsBookingId, status, cancellable]
        $stubs = [
            self::CONFIRMED_BOOKING_UUID => [self::CONFIRMED_TCMS_BOOKING_ID, Booking::STATUS_CONFIRMED, true],
            self::ON_HOLD_BOOKING_UUID => [self::ON_HOLD_TCMS_BOOKING_ID, Booking::STATUS_ON_HOLD, true],
            self::NOT_CANCELLABLE_BOOKING_UUID => [self::NOT_CANCELLABLE_TCMS_BOOKING_ID, Booking::STATUS_CONFIRMED, false],
            self::CANCEL_FAILS_BOOKING_UUID => [self::CANCEL_FAILS_TCMS_BOOKING_ID, Booking::STATUS_CONFIRMED, true],
        ];

        $completeBookingJson = json_encode([
            'productId' => self::VALID_PRODUCT_ID,
            'optionId' => self::VALID_OPTION_ID,
            'availabilityId' => self::VALID_AVAILABILITY_ID,
            'unitItems' => self::VALID_UNIT_ITEMS,
            'notes' => null,
            'resellerReference' => null,
            'contact' => null,
        ]);

        $this->bookingCancellationServiceMock = $this->getMockBuilder(BookingCancellationService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBookingByUuid', 'getBooking'])
            ->getMock();
        $this->bookingCancellationServiceMock->tourCMSService = $this->tourCMSServiceMock;
        $this->bookingCancellationServiceMock->logger = $this->getLoggerMock();

        $this->bookingCancellationServiceMock
            ->method('getBookingByUuid')
            ->willReturnCallback(function (string $uuid) use ($stubs, $completeBookingJson): Booking {
                if (! array_key_exists($uuid, $stubs)) {
                    throw new InvalidBookingUUIDException($uuid);
                }

                [$bookingId] = $stubs[$uuid];

                $booking = new Booking;
                $booking->setUuid($uuid);
                $booking->setBookingId($bookingId);
                $booking->setAccountId(1);
                $booking->setChannelId(142);
                $booking->product_id = self::VALID_PRODUCT_ID;
                $booking->option_id = self::VALID_OPTION_ID;
                $booking->availability_id = self::VALID_AVAILABILITY_ID;
                $booking->unit_items = json_encode(self::VALID_UNIT_ITEMS);
                $booking->complete_booking_json = $completeBookingJson;

                return $booking;
            });

        $this->bookingCancellationServiceMock
            ->method('getBooking')
            ->willReturnCallback(function (Booking $bookingByUuid) use ($stubs, $product, $option, $availability): Booking {
                $uuid = $bookingByUuid->getUuid();
                [, $status, $cancellable] = $stubs[$uuid];

                $booking = new Booking;
                $booking->setUuid($uuid);
                $booking->setBookingId($bookingByUuid->getBookingId());
                $booking->setAccountId(1);
                $booking->setChannelId(142);
                $booking->setStatus($status);
                $booking->setCancellable($cancellable);
                $booking->setProduct($product);
                $booking->setOption($option);
                $booking->setAvailability($availability);
                $booking->setContact(new Contact);
                $booking->setUnits([]);
                $booking->setUtcExpiresAt(null);

                return $booking;
            });

        $this->instance(BookingCancellationService::class, $this->bookingCancellationServiceMock);

        $this->bookingUpdateService = $this->getMockBuilder(BookingUpdateService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $this->bookingUpdateService->tourCMSService = $this->tourCMSServiceMock;
        $this->bookingUpdateService->bookingCancellationService = $this->bookingCancellationServiceMock;
        $this->bookingUpdateService->bookingReservationService = $bookingReservationService;
        $this->bookingUpdateService->bookingConfirmationService = $bookingConfirmationService;
        $this->bookingUpdateService->bookingContactService = $bookingContactService;
        $this->bookingUpdateService->productService = $this->productService;
        $this->bookingUpdateService->optionService = new OptionService;
        $this->bookingUpdateService->availabilityService = $availabilityService;
        $this->bookingUpdateService->unitService = new UnitService;
        $this->bookingUpdateService->logger = $this->getLoggerMock();
        $this->bookingUpdateService->transformer = new BookingTransformer(BaseTransformer::FULL_TRANSFORM);

        $this->instance(BookingUpdateService::class, $this->bookingUpdateService);
    }
}
