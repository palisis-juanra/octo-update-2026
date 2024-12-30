<?php

namespace Tests\Feature;

use App\Http\Responses\OctoResponse;
use App\Services\JSONLogService;
use App\Services\TourCMSService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Mockery;
use SimpleXMLElement;
use Tests\FeatureTestCase;

class AvailabilityCalendarTest extends FeatureTestCase
{
    use RefreshDatabase;

    const AVAILABILIY_PATH = '/availability/calendar';
    const AUTH_HEADER_NAME = 'Authorization';
    const OCTO_INVALID_PATTERN_CREDENTIALS = 'Bearer NOVALIDKEY';
    const OCTO_VALID_PATTERN_CREDENTIALS = 'Bearer 1|142|abc';
    const VALID_PRODUCT_ID = 'TE_1_67|142';
    const INVALID_PRODUCT_ID = 'a';
    const VALID_OPTION_ID = 'START_TIME';
    const INVALID_OPTION_ID = 'a';
    const VALID_LOCAL_DATE = '2024-11-30';
    const INVALID_LOCAL_DATE = 'aaa';


    public SimpleXMLElement $datesAndDealsXML;
    public function setUp(): void
    {
        parent::setUp();

        $this->datesAndDealsXML = simplexml_load_string(file_get_contents('tests/TourCMSResponses/datesAndDeals.xml'));

        App::bind(TourCMSService::class, function ($app) {

            $tourCMSServiceMock = Mockery::mock(TourCMSService::class)->makePartial();
            $tourCMSServiceMock->shouldReceive('showTourDatesAndDeals')->zeroOrMoreTimes()->andReturn($this->datesAndDealsXML);

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
        $response = $this->post(
            self::AVAILABILIY_PATH,
            [
                'optionId' => self::VALID_OPTION_ID,
                'localDateStart' => self::VALID_LOCAL_DATE,
                'localDateEnd' => self::VALID_LOCAL_DATE
            ],
            [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]
        );

        $response->assertBadRequest();
    }

    public function test_whenWeDontSendOptionId_thenWeGetBadRequestResponse(): void
    {
        $response = $this->post(
            self::AVAILABILIY_PATH,
            [
                'productId' => self::VALID_PRODUCT_ID,
                'localDateStart' => self::VALID_LOCAL_DATE,
                'localDateEnd' => self::VALID_LOCAL_DATE
            ],
            [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]
        );

        $response->assertBadRequest();
    }

    public function test_whenWeDontSendlocalDateStart_thenWeGetBadRequestResponse(): void
    {
        $response = $this->post(
            self::AVAILABILIY_PATH,
            [
                'productId' => self::VALID_PRODUCT_ID,
                'optionId' => self::VALID_OPTION_ID,
                'localDateEnd' => self::VALID_LOCAL_DATE
            ],
            [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]
        );

        $response->assertBadRequest();
    }

    public function test_whenWeDontSendLocalDateEnd_thenWeGetBadRequestResponse(): void
    {
        $response = $this->post(
            self::AVAILABILIY_PATH,
            [
                'productId' => self::VALID_PRODUCT_ID,
                'optionId' => self::VALID_OPTION_ID,
                'localDateStart' => self::VALID_LOCAL_DATE,
            ],
            [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]
        );

        $response->assertBadRequest();
    }

    public function test_whenWeSendInvalidProductId_thenWeGetBadRequestResponse(): void
    {
        $response = $this->post(
            self::AVAILABILIY_PATH,
            [
                'productId' => self::INVALID_PRODUCT_ID,
                'optionId' => self::VALID_OPTION_ID,
                'localDateStart' => self::VALID_LOCAL_DATE,
                'localDateEnd' => self::VALID_LOCAL_DATE
            ],
            [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]
        );

        $responseData = $response->decodeResponseJson();
        $response->assertBadRequest();
        $this->assertEquals(OctoResponse::ERROR_CODE_INVALID_PRODUCT_ID, $responseData['error']);
        $this->assertEquals(OctoResponse::ERROR_MESSAGE_INVALID_PRODUCT_ID, $responseData['errorMessage']);
    }

    public function test_whenWeSendInvalidOptionId_thenWeGetBadRequestResponse(): void
    {
        $response = $this->post(
            self::AVAILABILIY_PATH,
            [
                'productId' => self::VALID_PRODUCT_ID,
                'optionId' => self::INVALID_OPTION_ID,
                'localDateStart' => self::VALID_LOCAL_DATE,
                'localDateEnd' => self::VALID_LOCAL_DATE
            ],
            [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]
        );

        $responseData = $response->decodeResponseJson();
        $response->assertBadRequest();
        $this->assertEquals(OctoResponse::ERROR_CODE_INVALID_OPTION_ID, $responseData['error']);
        $this->assertEquals(OctoResponse::ERROR_MESSAGE_INVALID_OPTION_ID, $responseData['errorMessage']);
    }

    public function test_whenWeSendInvalidLocalDateStart_thenWeGetBadRequestResponse(): void
    {
        $response = $this->post(
            self::AVAILABILIY_PATH,
            [
                'productId' => self::VALID_PRODUCT_ID,
                'optionId' => self::VALID_OPTION_ID,
                'localDateStart' => self::INVALID_LOCAL_DATE,
                'localDateEnd' => self::VALID_LOCAL_DATE
            ],
            [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]
        );

        $response->assertBadRequest();
    }

    public function test_whenWeSendInvalidLocalDateEnd_thenWeGetBadRequestResponse(): void
    {
        $response = $this->post(
            self::AVAILABILIY_PATH,
            [
                'productId' => self::VALID_PRODUCT_ID,
                'optionId' => self::VALID_OPTION_ID,
                'localDateStart' => self::VALID_LOCAL_DATE,
                'localDateEnd' => self::INVALID_LOCAL_DATE
            ],
            [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]
        );

        $response->assertBadRequest();
    }

    public function test_whenWeSendCorrectData_thenWeCallToTourcmsApi()
    {

        $response = $this->post(
            self::AVAILABILIY_PATH,
            [
                'productId' => self::VALID_PRODUCT_ID,
                'optionId' => self::VALID_OPTION_ID,
                'localDateStart' => self::VALID_LOCAL_DATE,
                'localDateEnd' => self::VALID_LOCAL_DATE
            ],
            [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]
        );

        $responseData = $response->decodeResponseJson();
        $response->assertOk();

        $this->assertNotEmpty($responseData);
    }
}

