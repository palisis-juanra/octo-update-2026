<?php

namespace Tests\Feature;

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

    public const AUTH_HEADER_NAME = 'Authorization';
    public const OCTO_INVALID_PATTERN_CREDENTIALS = 'Bearer NOVALIDKEY';
    public const OCTO_VALID_PATTERN_CREDENTIALS = 'Bearer 1|142|abc';
    public const VALID_PRODUCT_ID = 'TE_1_67|142';
    public const INVALID_PRODUCT_ID = 'a';
    public const VALID_OPTION_ID = 'START_TIME';
    public const INVALID_OPTION_ID = 'a';
    public const INVALID_UNIT_ID = 'aaaa';
    public const VALID_LOCAL_DATE = '2024-11-30';
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
        $this->showTourXML->tour->distribution_identifier = 'TE_1_67';
        $this->showTourXML->tour->channel_id = 142;
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
        $tourCMSServiceMock->shouldReceive('showChannel')->zeroOrMoreTimes()->andReturn($this->showChannelXML);
        $tourCMSServiceMock->shouldReceive('showTour')->zeroOrMoreTimes()->andReturn($this->showTourXML);
        $tourCMSServiceMock->shouldReceive('showTourDepartures')->between(1, 10)->andReturn($this->showTourDeparturesXML);
    
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

    public function test_whenNoPricingAndMultiDate_thenShowTourDepartureIsCalledOnce()
    {
        $tourCMSServiceMock = Mockery::mock(TourCMSService::class)->makePartial();
        $tourCMSServiceMock->shouldReceive('showChannel')->zeroOrMoreTimes()->andReturn($this->showChannelXML);
        $tourCMSServiceMock->shouldReceive('showTour')->zeroOrMoreTimes()->andReturn($this->showTourXML);
        $tourCMSServiceMock->shouldReceive('showTourDepartures')->between(1, 10)->andReturn($this->showTourDeparturesXML);

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
        $tourCMSServiceMock->shouldReceive('showChannel')->zeroOrMoreTimes()->andReturn($this->showChannelXML);
        $tourCMSServiceMock->shouldReceive('showTour')->zeroOrMoreTimes()->andReturn($this->showTourXML);
        $tourCMSServiceMock->shouldReceive('showTourDepartures')->because(2, 10)->andReturn($this->showTourDeparturesXML);

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
}