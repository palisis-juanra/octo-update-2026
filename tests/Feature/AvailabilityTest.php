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
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    const AVAILABILIY_PATH = '/availability';
    const AUTH_HEADER_NAME = 'Authorization';
    const OCTO_INVALID_PATTERN_CREDENTIALS = 'Bearer NOVALIDKEY';
    const OCTO_VALID_PATTERN_CREDENTIALS = 'Bearer 1|142|abc';
    const VALID_PRODUCT_ID = 'TE_1_67|142';
    const INVALID_PRODUCT_ID = 'a';
    const VALID_OPTION_ID = 'START_TIME';
    const INVALID_OPTION_ID = 'a';
    const VALID_LOCAL_DATE = '2024-11-30';
    const INVALID_LOCAL_DATE = 'aaa';
    const VALID_UNIT_ID = 'TE_1_67|r1';
    const INVALID_UNIT_ID = 'aaaa';


    public SimpleXMLElement $showTourXML;
    public SimpleXMLElement $showTourDeparturesXML;
    public function setUp(): void
    {
        parent::setUp();

        $this->showTourXML = simplexml_load_string(file_get_contents('tests/TourCMSResponses/showTour.xml'));
        $this->showTourDeparturesXML = simplexml_load_string(file_get_contents('tests/TourCMSResponses/showTourDepartures.xml'));

        App::bind(TourCMSService::class, function ($app) {

            $tourCMSServiceMock = Mockery::mock(TourCMSService::class)->makePartial();
            $tourCMSServiceMock->shouldReceive('showTour')->zeroOrMoreTimes()->andReturn($this->showTourXML);
            $tourCMSServiceMock->shouldReceive('showTourDepartures')->zeroOrMoreTimes()->andReturn($this->showTourDeparturesXML);

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

