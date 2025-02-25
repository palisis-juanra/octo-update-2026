<?php

namespace Tests\Feature;

use App\Http\Requests\OctoRequest;
use App\Http\Responses\OctoResponse;
use App\Services\AvailabilityService;
use App\Services\JSONLogService;
use App\Services\TourCMSService;
use App\Services\UnitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Mockery;
use SimpleXMLElement;
use Tests\FeatureTestCase;

class AvailabilityTest extends FeatureTestCase
{
    use RefreshDatabase;

    public const AVAILABILIY_PATH = '/availability';
    public const AUTH_HEADER_NAME = 'Authorization';
    public const OCTO_CAPABILITIES = 'Octo-Capabilities';
    public const OCTO_INVALID_PATTERN_CREDENTIALS = 'Bearer NOVALIDKEY';
    public const OCTO_VALID_PATTERN_CREDENTIALS = 'Bearer 1|142|abc';
    public const VALID_PRODUCT_ID = 'TE_1_67|142';
    public const INVALID_PRODUCT_ID = 'a';
    public const VALID_OPTION_ID = 'START_TIME';
    public const INVALID_OPTION_ID = 'a';
    public const VALID_LOCAL_DATE = '2024-11-30';
    public const INVALID_LOCAL_DATE = 'aaa';
    public const UNIT_ID_R1 = 'TE_1_67|142|r1';
    public const UNIT_ID_R2 = 'TE_1_67|142|r2';
    public const INVALID_UNIT_ID = 'aaaa';
    public const VALID_LOCAL_DATE_START = '2024-11-18';
    public const VALID_LOCAL_DATE_END = '2024-11-25';

    public SimpleXMLElement $showChannelXML;
    public SimpleXMLElement $showTourXML;
    public SimpleXMLElement $showTourDeparturesXML;
    public SimpleXMLElement $showTourDeparturesOneDayXML;
    public SimpleXMLElement $checkAvailXML;

    public function setUp(): void
    {
        parent::setUp();

        $this->showChannelXML = simplexml_load_file('tests/TourCMSResponses/showChannel.xml');
        $this->showTourXML = simplexml_load_file('tests/TourCMSResponses/showTour.xml');
        $this->showTourDeparturesXML = simplexml_load_file('tests/TourCMSResponses/showTourDepartures.xml');
        $this->showTourDeparturesOneDayXML = simplexml_load_file('tests/TourCMSResponses/showTourDeparturesOneDay.xml');
        $this->checkAvailXML = simplexml_load_file('tests/TourCMSResponses/checkAvailability.xml');

        App::bind(TourCMSService::class, function ($app) {

            $tourCMSServiceMock = Mockery::mock(TourCMSService::class)->makePartial();
            $tourCMSServiceMock->shouldReceive('showTour')->zeroOrMoreTimes()->andReturn($this->showChannelXML);
            $tourCMSServiceMock->shouldReceive('showTour')->zeroOrMoreTimes()->andReturn($this->showTourXML);
            $tourCMSServiceMock->shouldReceive('showTourDepartures')->zeroOrMoreTimes()->andReturn($this->showTourDeparturesXML);
            $tourCMSServiceMock->shouldReceive('showChannel')->zeroOrMoreTimes()->andReturn($this->showChannelXML);

            return $tourCMSServiceMock;
        });

        App::bind(JSONLogService::class, function ($app) {

            $jsonLogServiceMock = Mockery::mock(JSONLogService::class)->makePartial();
            $jsonLogServiceMock->shouldReceive('info')->zeroOrMoreTimes()->andReturnNull();
            $jsonLogServiceMock->shouldReceive('error')->zeroOrMoreTimes()->andReturnNull();
            $jsonLogServiceMock->shouldReceive('getLogId')->zeroOrMoreTimes()->andReturns('');

            return $jsonLogServiceMock;
        });

    }

    public function test_whenWeDontSendProductId_thenWeGetBadRequestResponse(): void
    {
        $response = $this->post('/availability', [], [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]);
        
        $response->assertBadRequest();
    }

    public function test_whenWeSendInvalidProductId_thenWeGetBadRequestResponse(): void
    {
        $response = $this->post('/availability', ["productId" => self::INVALID_PRODUCT_ID], [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]);
        
        $response->assertBadRequest();
    }

    public function test_whenWeDontSendOptionId_thenWeGetBadRequestResponse(): void
    {
        $response = $this->post('/availability', [], [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]);

        $response->assertBadRequest();
    }

    public function test_whenWeSendInvalidOptionId_thenWeGetBadRequestResponse(): void
    {
        $response = $this->post('/availability', 
            [
                'productId' => self::VALID_PRODUCT_ID,
                'optionId' => self::INVALID_OPTION_ID,
            ], [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]
        );

        $responseData = $response->decodeResponseJson();
        $response->assertBadRequest();
        $this->assertEquals($responseData['error'], OctoResponse::ERROR_CODE_INVALID_OPTION_ID);
        $this->assertEquals($responseData['errorMessage'], OctoResponse::ERROR_MESSAGE_INVALID_OPTION_ID);
    }

    public function test_whenWeSendInvalidUnitId_thenWeGetBadRequestResponse(): void
    {
        $response = $this->post('/availability', 
            [
                'productId' => self::VALID_PRODUCT_ID,
                'optionId' => self::VALID_OPTION_ID,
                'localDate' => self::VALID_LOCAL_DATE,
                'units' => [
                    'id' => self::INVALID_UNIT_ID,
                    'quantity' => 1
                ]
            ], [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]
        );

        $responseData = $response->decodeResponseJson();
        $response->assertBadRequest();
        $this->assertEquals($responseData['error'], OctoResponse::ERROR_CODE_BAD_REQUEST);
        $this->assertEquals($responseData['errorMessage'], UnitService::ERROR_MESSAGE_INVALID_UNITS);
    }

    public function test_whenWeDontSendAnyDate_thenWeReceivedBadRequest(): void
    {
        $response = $this->post(
            '/availability', 
            [
                'productId' => self::VALID_PRODUCT_ID, 
                'optionId' => self::VALID_OPTION_ID,
            ], 
            [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]
        );

        $responseData = $response->decodeResponseJson();
        $response->assertBadRequest();
        $this->assertEquals($responseData['error'], OctoResponse::ERROR_CODE_BAD_REQUEST);
        $this->assertEquals($responseData['errorMessage'], AvailabilityService::ERROR_MESSAGE_AVAILABILITY_NEED_DATE);
    }

    public function test_whenWeSendLocalStartDateButNotLocalEndDate_thenWeGetBadRequest(): void
    {
        
        $response = $this->post(
            '/availability', 
            [
                'productId' => self::VALID_PRODUCT_ID, 
                'optionId' => self::VALID_OPTION_ID
            ], 
            [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]
        );

        $responseData = $response->decodeResponseJson();
        $response->assertBadRequest();
        $this->assertEquals($responseData['error'], OctoResponse::ERROR_CODE_BAD_REQUEST);
        $this->assertEquals($responseData['errorMessage'], AvailabilityService::ERROR_MESSAGE_AVAILABILITY_NEED_DATE);
    }

    public function test_whenWeSendCorrectData_thenWeCallToTourcmsApi()
    {

        $tourCMSServiceMock = Mockery::mock(TourCMSService::class)->makePartial();
        $tourCMSServiceMock->shouldReceive('showChannel')->once()->andReturn($this->showChannelXML);
        $tourCMSServiceMock->shouldReceive('showTour')->zeroOrMoreTimes()->andReturn($this->showTourXML);
        $tourCMSServiceMock->shouldReceive('showTourDepartures')->once()->andReturn($this->showTourDeparturesXML);
    
        App::instance(TourCMSService::class, $tourCMSServiceMock);

        $response = $this->post(
            '/availability', 
            [
                'productId' => self::VALID_PRODUCT_ID, 
                'optionId' => self::VALID_OPTION_ID,
                'localDate' => self::VALID_LOCAL_DATE
            ], 
            [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]
        );

        $responseData = $response->decodeResponseJson();
        $response->assertOk();

        $this->assertNotEmpty($responseData);        
    }

    public function test_whenPricingIsAllowedAndIsMultiDate_thenShowTourDepartureIsCalledOnce()
    {
        $tourCMSServiceMock = Mockery::mock(TourCMSService::class)->makePartial();
        $tourCMSServiceMock->shouldReceive('showChannel')->once()->andReturn($this->showChannelXML);
        $tourCMSServiceMock->shouldReceive('showTour')->zeroOrMoreTimes()->andReturn($this->showTourXML);
        $tourCMSServiceMock->shouldReceive('showTourDepartures')->once()->andReturn($this->showTourDeparturesXML);
    
        App::instance(TourCMSService::class, $tourCMSServiceMock);
    
        $response = $this->post(
            '/availability', 
            [
                'productId' => self::VALID_PRODUCT_ID, 
                'optionId' => self::VALID_OPTION_ID,
                'localDateStart' => self::VALID_LOCAL_DATE_START,
                'localDateEnd' => self::VALID_LOCAL_DATE_END
            ], 
            [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS,
            self::OCTO_CAPABILITIES => 'pricing']
        );

    
        $responseData = $response->decodeResponseJson();
    
        $response->assertOk();
        $this->assertNotEmpty($responseData);
    }

    public function test_whenNoPricingAndMultiDate_thenShowTourDepartureIsCalledOnce()
    {
        $tourCMSServiceMock = Mockery::mock(TourCMSService::class)->makePartial();
        $tourCMSServiceMock->shouldReceive('showChannel')->once()->andReturn($this->showChannelXML);
        $tourCMSServiceMock->shouldReceive('showTour')->zeroOrMoreTimes()->andReturn($this->showTourXML);
        $tourCMSServiceMock->shouldReceive('showTourDepartures')->once()->andReturn($this->showTourDeparturesXML);

        App::instance(TourCMSService::class, $tourCMSServiceMock);
    
        // Petición al endpoint con datos válidos
        $response = $this->post(
            '/availability', 
            [
                'productId' => self::VALID_PRODUCT_ID, 
                'optionId' => self::VALID_OPTION_ID,
                'localDateStart' => self::VALID_LOCAL_DATE_START,
                'localDateEnd' => self::VALID_LOCAL_DATE_END
            ], 
            [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]
        );
    
        $responseData = $response->decodeResponseJson();
    
        $response->assertOk();
        $this->assertNotEmpty($responseData);
    }

    public function test_whenNoPricingAndSingleDate_thenShowTourDepartureIsCalledOnce()
    {
        $tourCMSServiceMock = Mockery::mock(TourCMSService::class)->makePartial();
        $tourCMSServiceMock->shouldReceive('showChannel')->once()->andReturn($this->showChannelXML);
        $tourCMSServiceMock->shouldReceive('showTour')->zeroOrMoreTimes()->andReturn($this->showTourXML);
        $tourCMSServiceMock->shouldReceive('showTourDepartures')->once()->andReturn($this->showTourDeparturesXML);

        App::instance(TourCMSService::class, $tourCMSServiceMock);
    
        // Petición al endpoint con datos válidos
        $response = $this->post(
            '/availability', 
            [
                'productId' => self::VALID_PRODUCT_ID, 
                'optionId' => self::VALID_OPTION_ID,
                'localDate' => self::VALID_LOCAL_DATE
            ], 
            [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]
        );
    
        $responseData = $response->decodeResponseJson();
    
        $response->assertOk();
        $this->assertNotEmpty($responseData);
    }

    public function test_whenPricingAndSingleDate_thenCheckAvailabilityIsCalledOnce()
    {

        $tourCMSServiceMock = Mockery::mock(TourCMSService::class)->makePartial();
        $tourCMSServiceMock->shouldReceive('showChannel')->once()->andReturn($this->showChannelXML);
        $tourCMSServiceMock->shouldReceive('showTour')->zeroOrMoreTimes()->andReturn($this->showTourXML);
        $tourCMSServiceMock->shouldReceive('showTourDepartures')->once()->andReturn($this->showTourDeparturesXML);
        $tourCMSServiceMock->shouldReceive('checkAvailability')->once()->andReturn($this->checkAvailXML);

        App::instance(TourCMSService::class, $tourCMSServiceMock);

        $response = $this->post(
            '/availability', 
            [
                'productId' => self::VALID_PRODUCT_ID, 
                'optionId' => self::VALID_OPTION_ID,
                'localDate' => self::VALID_LOCAL_DATE
            ], 
            [
                self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS,
                self::OCTO_CAPABILITIES => OctoRequest::CAPABILITIES_PRICING
            ]
        );

        $responseData = $response->decodeResponseJson();

        $response->assertOk();
        $this->assertNotEmpty($responseData);
    }

    public function test_whenPricingCapabilityIsSent_thenWeReturnCorrectPricing(): void 
    {
        $tourCMSServiceMock = $this->getMockBuilder(TourCMSService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['showChannel', 'showTour', 'showTourDepartures', 'checkAvailability'])
            ->getMock();

        $tourCMSServiceMock
            ->method('showChannel')
            ->willReturn($this->showChannelXML);

        $tourCMSServiceMock
            ->method('showTour')
            ->willReturn($this->showTourXML);

        $tourCMSServiceMock
            ->method('showTourDepartures')
            ->willReturn($this->showTourDeparturesOneDayXML);
        
        $component = $this->checkAvailXML->available_components->component[0];
        $component->start_date = $this->showTourDeparturesOneDayXML->tour->dates_and_prices->departure[0]->start_date;
        $component->date_id = $this->showTourDeparturesOneDayXML->tour->dates_and_prices->departure[0]->departure_id;

        $tourCMSServiceMock
            ->method('checkAvailability')
            ->willReturn($this->checkAvailXML);

        App::instance(TourCMSService::class, $tourCMSServiceMock);


        $response = $this->post(
            '/availability', 
            [
                'productId' => self::VALID_PRODUCT_ID, 
                'optionId' => self::VALID_OPTION_ID,
                'localDate' => self::VALID_LOCAL_DATE,
                'unitItems' => [
                    [
                        "unitId" => self::UNIT_ID_R1
                    ],
                    [
                        "unitId" => self::UNIT_ID_R2
                    ],
                ]
            ], 
            [
                self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS,
                self::OCTO_CAPABILITIES => OctoRequest::CAPABILITIES_PRICING
            ]
        );

        $expectedNetPrice = $component->net_price * 100;
        $expectedOriginalPrice  = $component->total_price * 100;
        $expectedRetailPrice  = $component->total_price * 100;

        $response
            ->assertOk()
            ->assertJsonFragment([
                "pricing" => [
                    "currency" => "USD",
                    "currencyPrecision" => 2,
                    'includedTaxes' => [],
                    "net" => $expectedNetPrice,
                    "original" => $expectedOriginalPrice,
                    "retail" => $expectedRetailPrice
                ]
            ]);
    }

    public function test_whenPricingCapabilityIsSent_thenWeReturnCorrectUnitPricing(): void
    {
        $tourCMSServiceMock = $this->getMockBuilder(TourCMSService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['showChannel', 'showTour', 'showTourDepartures', 'checkAvailability'])
            ->getMock();

        $tourCMSServiceMock
            ->method('showChannel')
            ->willReturn($this->showChannelXML);

        $tourCMSServiceMock
            ->method('showTour')
            ->willReturn($this->showTourXML);

        $tourCMSServiceMock
            ->method('showTourDepartures')
            ->willReturn($this->showTourDeparturesOneDayXML);
        
        $component = $this->checkAvailXML->available_components->component[0];
        $component->start_date = $this->showTourDeparturesOneDayXML->tour->dates_and_prices->departure[0]->start_date;
        $component->date_id = $this->showTourDeparturesOneDayXML->tour->dates_and_prices->departure[0]->departure_id;

        $tourCMSServiceMock
            ->method('checkAvailability')
            ->willReturn($this->checkAvailXML);

        App::instance(TourCMSService::class, $tourCMSServiceMock);


        $response = $this->post(
            '/availability', 
            [
                'productId' => self::VALID_PRODUCT_ID, 
                'optionId' => self::VALID_OPTION_ID,
                'localDate' => self::VALID_LOCAL_DATE,
                'unitItems' => [
                    [
                        "unitId" => self::UNIT_ID_R1
                    ],
                    [
                        "unitId" => self::UNIT_ID_R2
                    ],
                ]
            ], 
            [
                self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS,
                self::OCTO_CAPABILITIES => OctoRequest::CAPABILITIES_PRICING
            ]
        );

        $departure = $this->showTourDeparturesOneDayXML->tour->dates_and_prices->departure[0];

        $expectedR1NetPrice = $departure->main_price->net_price * 100;
        $expectedR1RetailPrice  = $departure->main_price->rate_price * 100;

        $expectedR2NetPrice = $departure->extra_rates->rate[0]->net_price * 100;
        $expectedR2RetailPrice  = $departure->extra_rates->rate[0]->rate_price * 100;

        $response
            ->assertOk()
            ->assertJsonFragment([
                "unitPricing" => [
                    [
                        "currency" => "USD",
                        "currencyPrecision" => 2,
                        "net" => $expectedR1NetPrice,
                        "retail" => $expectedR1RetailPrice,
                        "unitId" => self::UNIT_ID_R1
                    ],
                    [
                        "currency" => "USD",
                        "currencyPrecision" => 2,
                        "net" => $expectedR2NetPrice,
                        "retail" => $expectedR2RetailPrice,
                        "unitId" => self::UNIT_ID_R2
                    ]
                    
                ]
            ]);
    }
    
}