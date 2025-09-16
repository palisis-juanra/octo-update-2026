<?php

namespace Tests\Feature;

use App\Facades\JSONLog;
use App\Http\Requests\OctoRequest;
use App\Http\Responses\OctoResponse;
use App\Services\AvailabilityService;
use App\Services\TourCMSService;
use App\Services\UnitService;
use App\Services\XMLService;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\App;
use Mockery;
use SimpleXMLElement;
use Tests\FeatureTestCase;

/**
 * This test class is used to test the /availability endpoint when the 
 * pricing capabilty header is sent in the request.
 */

class PricingAvailabilityTest extends FeatureTestCase
{
    public const VALID_PRODUCT_ID = 'TE_1_67|142';
    public const VALID_OPTION_ID = 'START_TIME';
    public const UNIT_ID_R1 = 'TE_1_67|142|r1';
    public const UNIT_ID_R2 = 'TE_1_67|142|r2';
    public const VALID_LOCAL_DATE = '2024-11-30';
    public const VALID_LOCAL_DATE_START = '2024-11-18';
    public const VALID_LOCAL_DATE_END = '2024-11-25';
    public const QUANTITY_BASED_PRODUCT_ID = 'TE_1_262|142';
    public const QUANTITY_BASED_OPTION_ID = 'SINGLE';
    public const QUANTITY_BASED_UNIT_ID = self::QUANTITY_BASED_PRODUCT_ID . '|r1';
    public const QUANTITY_BASED_INVALID_UNIT_ID = self::QUANTITY_BASED_PRODUCT_ID . '|r2';

    public SimpleXMLElement $showChannelXML;
    public SimpleXMLElement $showTourXML;
    public SimpleXMLElement $showTourDeparturesXML;
    public SimpleXMLElement $showQuantityBasedPricingTourXML;
    public SimpleXMLElement $showQuantityBasedPricingTourDeparturesXML;
    public SimpleXMLElement $showQuantityBasedPricingTourDeparturesOneDayXML;
    public SimpleXMLElement $checkAvailXML;

    public function setUp(): void
    {
        parent::setUp();
       
        $this->showChannelXML = simplexml_load_file('tests/TourCMSResponses/showChannel.xml');

        $this->showTourXML = simplexml_load_file('tests/TourCMSResponses/showTour.xml');
        
        $this->showTourXML->tour->tour_id = 67;
        $this->showTourXML->tour->channel_id = 142;
        $this->showTourXML->tour->distribution_identifier = 'TE_1_67';
        
        $this->showTourDeparturesXML = simplexml_load_file(filename: 'tests/TourCMSResponses/showTourDepartures.xml');

        $this->showQuantityBasedPricingTourXML = simplexml_load_file('./tests/TourCMSResponses/showQuantityBasedPricingTour.xml');
        $this->showQuantityBasedPricingTourDeparturesXML = simplexml_load_file('tests/TourCMSResponses/showQuantityBasedTourDepartures.xml');
        $this->showQuantityBasedPricingTourDeparturesOneDayXML = simplexml_load_file('tests/TourCMSResponses/showQuantityBasedTourDeparturesForOneDay.xml');
        
        $this->checkAvailXML = simplexml_load_file('tests/TourCMSResponses/checkAvailability.xml');
    }

    public function test_whenPricingIsAllowedAndIsMultiDate_thenShowTourDepartureIsCalledOnce()
    {
        $tourCMSServiceMock = Mockery::mock(TourCMSService::class)->makePartial();
        $tourCMSServiceMock->shouldReceive('showChannel')->zeroOrMoreTimes()->andReturn($this->showChannelXML);
        $tourCMSServiceMock->shouldReceive('showTour')->zeroOrMoreTimes()->andReturn($this->showTourXML);
        $tourCMSServiceMock->shouldReceive('showTourDepartures')->between(1, 10)->andReturn($this->showTourDeparturesXML);
    
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
            OctoRequest::CAPABILITIES_HEADER => 'pricing']
        );

    
        $responseData = $response->decodeResponseJson();
    
        $response->assertOk();
        $this->assertNotEmpty($responseData);
    }

    public function test_whenPricingAndSingleDate_thenCheckAvailabilityIsCalledOnce(): void
    {

        $tourCMSServiceMock = Mockery::mock(TourCMSService::class)->makePartial();
        $tourCMSServiceMock->shouldReceive('showChannel')->zeroOrMoreTimes()->andReturn($this->showChannelXML);
        $tourCMSServiceMock->shouldReceive('showTour')->zeroOrMoreTimes()->andReturn($this->showTourXML);
        $tourCMSServiceMock->shouldReceive('showTourDepartures')->between(1, 10)->andReturn($this->showTourDeparturesXML);
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
                OctoRequest::CAPABILITIES_HEADER => OctoRequest::CAPABILITIES_PRICING
            ]
        );

        $responseData = $response->decodeResponseJson();

        $response->assertOk();
        $this->assertNotEmpty($responseData);
    }

    public function test_whenRequestIsSingleDay_thenWeReturnCorrectPricing(): void 
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
            ->willReturn($this->showTourDeparturesXML);
        
        $component = $this->checkAvailXML->available_components->component[0];
        $component->start_date = $this->showTourDeparturesXML->tour->dates_and_prices->departure[0]->start_date;
        $component->date_id = $this->showTourDeparturesXML->tour->dates_and_prices->departure[0]->departure_id;

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
                'units' => [
                    [
                        "id" => self::UNIT_ID_R1,
                        "quantity" => 1
                    ],
                    [
                        "id" => self::UNIT_ID_R2,
                        "quantity" => 1
                    ],
                ]
            ], 
            [
                self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS,
                OctoRequest::CAPABILITIES_HEADER => OctoRequest::CAPABILITIES_PRICING
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

    public function test_whenRequestIsSingleDay_thenWeReturnCorrectUnitPricing(): void
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
            ->willReturn($this->showTourDeparturesXML);
        
        $component = $this->checkAvailXML->available_components->component[0];
        $component->start_date = $this->showTourDeparturesXML->tour->dates_and_prices->departure[0]->start_date;
        $component->date_id = $this->showTourDeparturesXML->tour->dates_and_prices->departure[0]->departure_id;

        $tourCMSServiceMock
            ->method('checkAvailability')
            ->willReturn($this->checkAvailXML);

        App::bind(JSONLog::class, function () { return $this->getJsonLogMock();});
        App::instance(TourCMSService::class, $tourCMSServiceMock);

        $response = $this->post(
            '/availability', 
            [
                'productId' => self::VALID_PRODUCT_ID, 
                'optionId' => self::VALID_OPTION_ID,
                'localDate' => self::VALID_LOCAL_DATE,
                'units' => [
                    [
                        "id" => self::UNIT_ID_R1,
                        "quantity" => 1
                    ],
                    [
                        "id" => self::UNIT_ID_R2,
                        "quantity" => 1
                    ]
                ]
            ], 
            [
                self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS,
                OctoRequest::CAPABILITIES_HEADER => OctoRequest::CAPABILITIES_PRICING
            ]
        );

        $departure = $this->showTourDeparturesXML->tour->dates_and_prices->departure[0];

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
                        "includedTaxes" => [],
                        "net" => $expectedR1NetPrice,
                        "retail" => $expectedR1RetailPrice,
                        "original" => $expectedR1RetailPrice,
                        "unitId" => self::UNIT_ID_R1
                    ],
                    [
                        "currency" => "USD",
                        "currencyPrecision" => 2,
                        "includedTaxes" => [],
                        "net" => $expectedR2NetPrice,
                        "retail" => $expectedR2RetailPrice,
                        "original" => $expectedR2RetailPrice,
                        "unitId" => self::UNIT_ID_R2
                    ]
                    
                ]
            ]);
    }

    // Quantity based pricing tests
    public function test_whenProductHaveQuantityBasedPricing_thenWeOnlyAcceptRate1(): void
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
            ->willReturn($this->showQuantityBasedPricingTourXML);

        $tourCMSServiceMock
            ->method('showTourDepartures')
            ->willReturn($this->showTourDeparturesXML);

        $component = $this->checkAvailXML->available_components->component[0];
        $component->start_date = $this->showTourDeparturesXML->tour->dates_and_prices->departure[0]->start_date;
        $component->date_id = $this->showTourDeparturesXML->tour->dates_and_prices->departure[0]->departure_id;

        $tourCMSServiceMock
            ->method('checkAvailability')
            ->willReturn($this->checkAvailXML);

        App::instance(TourCMSService::class, $tourCMSServiceMock);

        $response = $this->post(
            '/availability',
            [
                'productId' => self::QUANTITY_BASED_PRODUCT_ID,
                'optionId' => self::QUANTITY_BASED_OPTION_ID,
                'localDate' => self::VALID_LOCAL_DATE,
                'units' => [
                    [
                        "id" => self::QUANTITY_BASED_INVALID_UNIT_ID,
                        "quantity" => 1
                    ]
                ]
            ],
            [
                self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS,
                OctoRequest::CAPABILITIES_HEADER => OctoRequest::CAPABILITIES_PRICING
            ]
        );

        $response->assertStatus(400);
        $response->assertJsonFragment([
            OctoResponse::FIELD_ERROR => OctoResponse::ERROR_CODE_INVALID_UNIT_ID
        ]);
    }

    public function test_whenProductHaveQuantityBasedPricingAndIsSingleDay_thenWeReturnCorrectPricing(): void
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
            ->willReturn($this->showQuantityBasedPricingTourXML);

        $tourCMSServiceMock
            ->method('showTourDepartures')
            ->willReturn($this->showTourDeparturesXML);
        
        $component = $this->checkAvailXML->available_components->component[0];
        $component->start_date = $this->showTourDeparturesXML->tour->dates_and_prices->departure[0]->start_date;
        $component->date_id = $this->showTourDeparturesXML->tour->dates_and_prices->departure[0]->departure_id;

        $tourCMSServiceMock
            ->method('checkAvailability')
            ->willReturn($this->checkAvailXML);

        App::instance(TourCMSService::class, $tourCMSServiceMock);


        $response = $this->post(
            '/availability', 
            [
                'productId' => self::QUANTITY_BASED_PRODUCT_ID, 
                'optionId' => self::QUANTITY_BASED_OPTION_ID,
                'localDate' => self::VALID_LOCAL_DATE,
                'units' => [
                    [
                        "id" => self::QUANTITY_BASED_UNIT_ID,
                        "quantity" => 2
                    ]
                ]
            ], 
            [
                self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS,
                OctoRequest::CAPABILITIES_HEADER => OctoRequest::CAPABILITIES_PRICING
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

    public function test_whenProductHaveQuantityBasedPricingWithUnitsInRequestAndIsSingleDay_thenWeReturnCorrectUnitPricing(): void
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
            ->willReturn($this->showQuantityBasedPricingTourXML);

        $tourCMSServiceMock
            ->method('showTourDepartures')
            ->willReturn($this->showQuantityBasedPricingTourDeparturesOneDayXML);
        
        $component = $this->checkAvailXML->available_components->component[0];
        $component->start_date = $this->showQuantityBasedPricingTourDeparturesOneDayXML->tour->dates_and_prices->departure[0]->start_date;
        $component->date_id = $this->showQuantityBasedPricingTourDeparturesOneDayXML->tour->dates_and_prices->departure[0]->departure_id;

        $tourCMSServiceMock
            ->method('checkAvailability')
            ->willReturn($this->checkAvailXML);

        App::instance(TourCMSService::class, $tourCMSServiceMock);


        // If unit quantity is 1, we should return the price for the r1

        $response = $this->post(
            '/availability', 
            [
                'productId' => self::QUANTITY_BASED_PRODUCT_ID, 
                'optionId' => self::QUANTITY_BASED_OPTION_ID,
                'localDate' => self::VALID_LOCAL_DATE,
                'units' => [
                    [
                        "id" => self::QUANTITY_BASED_UNIT_ID,
                        "quantity" => 2
                    ]
                ]
            ], 
            [
                self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS,
                OctoRequest::CAPABILITIES_HEADER => OctoRequest::CAPABILITIES_PRICING
            ]
        );

        // for quantity 2, we should return the price for the r2...

        $departure = $this->showQuantityBasedPricingTourDeparturesOneDayXML->tour->dates_and_prices->departure[0];
        $expectedNetPrice = $departure->extra_rates->rate[0]->rate_price * 100;
        $expectedOriginalPrice  = $departure->extra_rates->rate[0]->rate_price * 100;
        $expectedRetailPrice  = $departure->extra_rates->rate[0]->rate_price * 100;

        $response
            ->assertOk()
            ->assertJsonFragment([
                "unitPricing" => [
                    [
                        "unitId" => self::QUANTITY_BASED_UNIT_ID,
                        "currency" => (string) $this->showQuantityBasedPricingTourXML->tour->sale_currency,
                        "currencyPrecision" => 2,
                        'includedTaxes' => [],
                        "net" => $expectedNetPrice,
                        "original" => $expectedOriginalPrice,
                        "retail" => $expectedRetailPrice
                    ]
                ]
            ]);
    }

    public function test_whenProductHaveQuantityBasedPricingWithoutUnitsInRequestAndIsSingleDay_thenWeUseMinBookingSizeToGetRatePricing(): void
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
            ->willReturn($this->showQuantityBasedPricingTourXML);

        $tourCMSServiceMock
            ->method('showTourDepartures')
            ->willReturn($this->showQuantityBasedPricingTourDeparturesOneDayXML);
        
        $component = $this->checkAvailXML->available_components->component[0];
        $component->start_date = $this->showQuantityBasedPricingTourDeparturesOneDayXML->tour->dates_and_prices->departure[0]->start_date;
        $component->date_id = $this->showQuantityBasedPricingTourDeparturesOneDayXML->tour->dates_and_prices->departure[0]->departure_id;

        $tourCMSServiceMock
            ->method('checkAvailability')
            ->willReturn($this->checkAvailXML);

        App::instance(TourCMSService::class, $tourCMSServiceMock);


        $this->showQuantityBasedPricingTourXML->tour->min_booking_size = 1;

        $response = $this->post(
            '/availability',
            [
                'productId' => self::QUANTITY_BASED_PRODUCT_ID, 
                'optionId' => self::QUANTITY_BASED_OPTION_ID,
                'localDate' => self::VALID_LOCAL_DATE
            ], 
            [
                self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS,
                OctoRequest::CAPABILITIES_HEADER => OctoRequest::CAPABILITIES_PRICING
            ]
        );

        

        $departure = $this->showQuantityBasedPricingTourDeparturesOneDayXML->tour->dates_and_prices->departure[0];
        $expectedNetPrice = $departure->main_price->rate_price * 100;
        $expectedOriginalPrice  = $departure->main_price->rate_price * 100;
        $expectedRetailPrice  = $departure->main_price->rate_price * 100;

        $response
            ->assertOk()
            ->assertJsonFragment([
                "unitPricing" => [
                    [
                        "unitId" => self::QUANTITY_BASED_UNIT_ID,
                        "currency" => (string) $this->showQuantityBasedPricingTourXML->tour->sale_currency,
                        "currencyPrecision" => 2,
                        'includedTaxes' => [],
                        "net" => $expectedNetPrice,
                        "original" => $expectedOriginalPrice,
                        "retail" => $expectedRetailPrice
                    ]
                ]
            ]);
        
        // With min booking size 2, we should return the price for the r2...
        $this->showQuantityBasedPricingTourXML->tour->min_booking_size = 2;
        $tourCMSServiceMock
            ->method('showTour')
            ->willReturn($this->showQuantityBasedPricingTourXML);
        App::instance(TourCMSService::class, $tourCMSServiceMock);

        $response = $this->post(
            '/availability',
            [
                'productId' => self::QUANTITY_BASED_PRODUCT_ID, 
                'optionId' => self::QUANTITY_BASED_OPTION_ID,
                'localDate' => self::VALID_LOCAL_DATE
            ], 
            [
                self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS,
                OctoRequest::CAPABILITIES_HEADER => OctoRequest::CAPABILITIES_PRICING
            ]
        );

        $departure = $this->showQuantityBasedPricingTourDeparturesOneDayXML->tour->dates_and_prices->departure[0];
        $expectedNetPrice = $departure->extra_rates->rate[0]->rate_price * 100;
        $expectedOriginalPrice  = $departure->extra_rates->rate[0]->rate_price * 100;
        $expectedRetailPrice  = $departure->extra_rates->rate[0]->rate_price * 100;

        $response
            ->assertOk()
            ->assertJsonFragment([
                "unitPricing" => [
                    [
                        "unitId" => self::QUANTITY_BASED_UNIT_ID,
                        "currency" => (string) $this->showQuantityBasedPricingTourXML->tour->sale_currency,
                        "currencyPrecision" => 2,
                        'includedTaxes' => [],
                        "net" => $expectedNetPrice,
                        "original" => $expectedOriginalPrice,
                        "retail" => $expectedRetailPrice
                    ]
                ]
            ]);
    }

    public function test_whenProductHaveQuantityBasedPricingAndQuantityIsHigherThanConfiguredRates_thenReturnUnitPriceOfHigherRate(): void
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
            ->willReturn($this->showQuantityBasedPricingTourXML);

        $tourCMSServiceMock
            ->method('showTourDepartures')
            ->willReturn($this->showQuantityBasedPricingTourDeparturesOneDayXML);
        
        $component = $this->checkAvailXML->available_components->component[0];
        $component->start_date = $this->showQuantityBasedPricingTourDeparturesOneDayXML->tour->dates_and_prices->departure[0]->start_date;
        $component->date_id = $this->showQuantityBasedPricingTourDeparturesOneDayXML->tour->dates_and_prices->departure[0]->departure_id;

        $tourCMSServiceMock
            ->method('checkAvailability')
            ->willReturn($this->checkAvailXML);

        App::instance(TourCMSService::class, $tourCMSServiceMock);


        $this->showQuantityBasedPricingTourXML->tour->min_booking_size = 1;

        $response = $this->post(
            '/availability',
            [
                'productId' => self::QUANTITY_BASED_PRODUCT_ID, 
                'optionId' => self::QUANTITY_BASED_OPTION_ID,
                'localDate' => self::VALID_LOCAL_DATE,
                'units' => [
                    [
                        'id' => self::QUANTITY_BASED_UNIT_ID,
                        'quantity' => 22
                    ]
                ]
            ], 
            [
                self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS,
                OctoRequest::CAPABILITIES_HEADER => OctoRequest::CAPABILITIES_PRICING
            ]
        );

        

        $departure = $this->showQuantityBasedPricingTourDeparturesOneDayXML->tour->dates_and_prices->departure[0];
        $rates = XMLService::getArrayFromXmlNode($departure->extra_rates, 'rate');
        $rate = array_pop($rates);
        $expectedNetPrice = isset($rate->net_price) ? ($rate->net_price * 100) : null;
        $expectedOriginalPrice  = $rate->rate_price * 100;
        $expectedRetailPrice  = $rate->rate_price * 100;

        $response
            ->assertOk()
            ->assertJsonFragment([
                "unitPricing" => [
                    [
                        "unitId" => self::QUANTITY_BASED_UNIT_ID,
                        "currency" => (string) $this->showQuantityBasedPricingTourXML->tour->sale_currency,
                        "currencyPrecision" => 2,
                        'includedTaxes' => [],
                        "net" => $expectedNetPrice,
                        "original" => $expectedOriginalPrice,
                        "retail" => $expectedRetailPrice
                    ]
                ]
            ]);
    }

    public function test_whenProductHaveQuantityBasedPricingIsMultiDayAndQuantityIsHigherThanConfiguredRates_thenReturnTotalPriceOfHigherRate(): void
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
            ->willReturn($this->showQuantityBasedPricingTourXML);

        $tourCMSServiceMock
            ->method('showTourDepartures')
            ->willReturn($this->showQuantityBasedPricingTourDeparturesOneDayXML);
        
        $component = $this->checkAvailXML->available_components->component[0];
        $component->start_date = $this->showQuantityBasedPricingTourDeparturesOneDayXML->tour->dates_and_prices->departure[0]->start_date;
        $component->date_id = $this->showQuantityBasedPricingTourDeparturesOneDayXML->tour->dates_and_prices->departure[0]->departure_id;

        $tourCMSServiceMock
            ->method('checkAvailability')
            ->willReturn($this->checkAvailXML);

        App::instance(TourCMSService::class, $tourCMSServiceMock);


        $this->showQuantityBasedPricingTourXML->tour->min_booking_size = 1;
        $quantity = 22;

        $response = $this->post(
            '/availability',
            [
                'productId' => self::QUANTITY_BASED_PRODUCT_ID, 
                'optionId' => self::QUANTITY_BASED_OPTION_ID,
                'localDateStart' => self::VALID_LOCAL_DATE_START,
                'localDateEnd' => self::VALID_LOCAL_DATE_END,
                'units' => [
                    [
                        'id' => self::QUANTITY_BASED_UNIT_ID,
                        'quantity' => $quantity
                    ]
                ]
            ], 
            [
                self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS,
                OctoRequest::CAPABILITIES_HEADER => OctoRequest::CAPABILITIES_PRICING
            ]
        );

        $departure = $this->showQuantityBasedPricingTourDeparturesOneDayXML->tour->dates_and_prices->departure[0];
        $rates = XMLService::getArrayFromXmlNode($departure->extra_rates, 'rate');
        $rate = array_pop($rates);
        $expectedNetPrice = isset($rate->net_price) ? ($rate->net_price * 100 * $quantity) : null;
        $expectedOriginalPrice  = $rate->rate_price * $quantity * 100;
        $expectedRetailPrice  = $rate->rate_price * $quantity * 100;

        $response
            ->assertOk()
            ->assertJsonFragment([
                "pricing" => [
                    "currency" => (string) $this->showQuantityBasedPricingTourXML->tour->sale_currency,
                    "currencyPrecision" => 2,
                    'includedTaxes' => [],
                    "net" => $expectedNetPrice,
                    "original" => $expectedOriginalPrice,
                    "retail" => $expectedRetailPrice
                ]
            ]);
    }

    public function test_whenProductHaveQuantityBasedPricingAndIsMultiDay_thenWeReturnPriceFromShowTourDepartures(): void
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
            ->willReturn($this->showQuantityBasedPricingTourXML);

        $tourCMSServiceMock
            ->method('showTourDepartures')
            ->willReturn($this->showQuantityBasedPricingTourDeparturesXML);
        
        // For this case check availability call is NOT made

        App::instance(TourCMSService::class, $tourCMSServiceMock);


        $response = $this->post(
            '/availability', 
            [
                'productId' => self::QUANTITY_BASED_PRODUCT_ID, 
                'optionId' => self::QUANTITY_BASED_OPTION_ID,
                AvailabilityService::PARAM_LOCAL_DATE_START => self::VALID_LOCAL_DATE_START,
                AvailabilityService::PARAM_LOCAL_DATE_END => self::VALID_LOCAL_DATE_END,
                'units' => [
                    [
                        "id" => self::QUANTITY_BASED_UNIT_ID,
                        "quantity" => 1
                    ]
                ]
            ], 
            [
                self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS,
                OctoRequest::CAPABILITIES_HEADER => OctoRequest::CAPABILITIES_PRICING
            ]
        );

        $departure = $this->showQuantityBasedPricingTourDeparturesXML->tour->dates_and_prices->departure[0];
        $expectedNetPrice = $departure->main_price->net_price * 100;
        $expectedOriginalPrice = $expectedRetailPrice = $departure->main_price->rate_price * 100;

        $response
            ->assertOk()
            ->assertJsonFragment([
                "pricing" => [
                    "currency" => (string) $this->showQuantityBasedPricingTourXML->tour->sale_currency,
                    "currencyPrecision" => 2,
                    'includedTaxes' => [],
                    "net" => $expectedNetPrice,
                    "original" => $expectedOriginalPrice,
                    "retail" => $expectedRetailPrice
                ]
            ]);
    }

    public function test_whenProductHaveQuantityBasedPricingAndIsMultiDay_thenWeReturnCorrectUnitPricingFromShowTourDepartures(): void
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
            ->willReturn($this->showQuantityBasedPricingTourXML);

        $tourCMSServiceMock
            ->method('showTourDepartures')
            ->willReturn($this->showQuantityBasedPricingTourDeparturesXML);
        
        // For this case check availability call is NOT made

        App::instance(TourCMSService::class, $tourCMSServiceMock);


        $response = $this->post(
            '/availability', 
            [
                'productId' => self::QUANTITY_BASED_PRODUCT_ID, 
                'optionId' => self::QUANTITY_BASED_OPTION_ID,
                AvailabilityService::PARAM_LOCAL_DATE_START => self::VALID_LOCAL_DATE_START,
                AvailabilityService::PARAM_LOCAL_DATE_END => self::VALID_LOCAL_DATE_END,
                'units' => [
                    [
                        "id" => self::QUANTITY_BASED_UNIT_ID,
                        "quantity" => 1
                    ]
                ]
            ], 
            [
                self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS,
                OctoRequest::CAPABILITIES_HEADER => OctoRequest::CAPABILITIES_PRICING
            ]
        );

        $departure = $this->showQuantityBasedPricingTourDeparturesXML->tour->dates_and_prices->departure[0];
        $expectedNetPrice = $departure->main_price->net_price * 100;
        $expectedOriginalPrice  = $departure->main_price->rate_price * 100;
        $expectedRetailPrice  = $departure->main_price->rate_price * 100;

        $response
            ->assertOk()
            ->assertJsonFragment([
                "unitPricing" => [
                    [
                        "currency" => (string) $this->showQuantityBasedPricingTourXML->tour->sale_currency,
                        "currencyPrecision" => 2,
                        "includedTaxes" => [],
                        "net" => $expectedNetPrice,
                        "original" => $expectedOriginalPrice,
                        "retail" => $expectedRetailPrice,
                        "unitId" => self::QUANTITY_BASED_UNIT_ID
                    ]
                ]
            ]);
    }
}