<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\Product;
use App\Services\BookingService;
use App\Services\LocaleService;
use App\Services\ProductMappingFactory;
use App\Services\ProductService;
use App\Services\TourPromotionService;
use App\Services\TourCMSService;
use App\Transformers\BaseTransformer;
use App\Transformers\BookingTransformer;
use PHPUnit\Framework\MockObject\MockObject;
use SimpleXMLElement;
use Tests\FeatureTestCase;

class BookingServiceTest extends FeatureTestCase
{
    public const SUPPLIER_REF = 'thisanoperatorRef';

    public SimpleXMLElement $showTourXML;
    public SimpleXMLElement $showBookingXML;
    public SimpleXMLElement $showChannelXML;
    public string $storedBookingJSON = '';
    public MockObject $loggerMock;
    public MockObject $productServiceMock;
    public Product $product;
    public array $options;

    public function setUp(): void
    {
        parent::setUp();
        $this->showTourXML = simplexml_load_file('tests/TourCMSResponses/InvalidChangesPostBooking/showTourInvalidDeliveryFormat.xml');
        $this->showBookingXML = simplexml_load_file('tests/TourCMSResponses/InvalidChangesPostBooking/showBookingWithDifferentOutdatesDeliveryFormats.xml');
        $this->storedBookingJSON = file_get_contents('tests/TourCMSResponses/InvalidChangesPostBooking/storedBookingInDB.json');
    }

    public function test_getBooking_whenThereHasBeenAnInvalidChangeToTheProduct_thenTheBookingMantainsTheDataFromTheCreating(): void
    {       
        
        $tourCMSServiceMock = $this->getMockBuilder(TourCMSService::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['showTour', 'showBooking'])
        ->getMock();
        $tourCMSServiceMock->method('showTour')->willReturn($this->showTourXML);
        $tourCMSServiceMock->method('showBooking')->willReturn($this->showBookingXML);

        $jsonLogServiceMock = $this->getMockBuilder(\App\Services\JSONLogService::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $availabilityServiceMock = $this->getMockBuilder(\App\Services\AvailabilityService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['generateAvailabilityFromBookingXML'])
            ->getMock();
        
        $bookingBuilderMock = $this->getMockBuilder(\App\Builders\BookingBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $bookingCheckerMock = $this->getMockBuilder(\App\Builders\BookingChecker::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $tourPromotionServiceMock = $this->getMockBuilder(TourPromotionService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productService = new ProductService(
            $tourCMSServiceMock,
            $jsonLogServiceMock,
            new LocaleService(),
            new ProductMappingFactory(),
            $tourPromotionServiceMock
        );
        $service = new BookingService($tourCMSServiceMock, $jsonLogServiceMock, $productService, $availabilityServiceMock, $bookingBuilderMock, $bookingCheckerMock);

        $transformer = new BookingTransformer(BaseTransformer::FULL_TRANSFORM);
        $booking = $service->getBooking($this->getBookingObject(), true);
        $transformedBooking = $transformer->transform($booking);
        $this->assertEquals($transformedBooking['product']['deliveryFormats'][0], json_decode($this->storedBookingJSON, true)['product']['deliveryFormats'][0]);
    }

    protected function getNotEmptyUnitItems(): array
    {
        return json_decode('[{"id": "TE_1_67|142|r1", "unit": [], "uuid": "7f44f437-6d81-43e0-9187-5ddd97419794", "status": "CONFIRMED", "ticket": null, "unitId": "TE_1_67|142|r1", "contact": [], "customerId": 12613, "utcRedeemedAt": null, "resellerReference": null, "supplierReference": ")M=bloa|Qi_O=MDPF+G#ya+Ri%-SfR"}, {"id": "TE_1_67|142|r2", "unit": [], "uuid": "2f8efe9d-beab-4b19-89ad-2ea2cc58c8a9", "status": "CONFIRMED", "ticket": null, "unitId": "TE_1_67|142|r2", "contact": [], "customerId": 12614, "utcRedeemedAt": null, "resellerReference": null, "supplierReference": ")M=bloa|Qi_O=MDPF+G#ya+Ri%-SfR"}]');
    }

    protected function getBookingObject(): Booking
    {
        $bookingObject = new Booking();
        $bookingObject->setBookingId('5074');
        $bookingObject->setUuid('a3fe8cf1-d9fb-4bde-b638-91464c8cc72c');
        $bookingObject->setOptionId('SUPPLIER_NOTE|ejemplo1_algo');
        $bookingObject->setProductId('TE_1_67|142');
        $bookingObject->setUnitItems($this->getNotEmptyUnitItems());
        $bookingObject->setCompleteJSONFromDB($this->storedBookingJSON);
        return $bookingObject;
    }
}