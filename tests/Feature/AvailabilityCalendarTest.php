<?php

namespace Tests\Feature;

use App\Http\Responses\OctoResponse;
use App\Services\AvailabilityCalendarService;
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

    const VALID_CHANNEL = '142';

    const INVALID_PRODUCT_ID = 'a';

    const VALID_OPTION_ID = 'START_TIME';

    const INVALID_OPTION_ID = 'a';

    const VALID_LOCAL_DATE = '2024-11-30';

    const INVALID_LOCAL_DATE = 'aaa';

    public SimpleXMLElement $showChannelXML;

    public SimpleXMLElement $tourXML;

    public SimpleXMLElement $datesAndDealsXML;

    protected function setUp(): void
    {
        parent::setUp();

        $this->showChannelXML = simplexml_load_string(file_get_contents('tests/TourCMSResponses/showChannel.xml'));
        $this->tourXML = simplexml_load_string(file_get_contents('tests/TourCMSResponses/showTour.xml'));
        $this->tourXML->tour->distribution_identifier = 'TE_1_67';
        $this->datesAndDealsXML = simplexml_load_string(file_get_contents('tests/TourCMSResponses/datesAndDeals.xml'));

        App::bind(TourCMSService::class, function ($app) {

            $tourCMSServiceMock = Mockery::mock(TourCMSService::class)->makePartial();
            $tourCMSServiceMock->shouldReceive('showChannel')->zeroOrMoreTimes()->andReturn($this->showChannelXML);
            $tourCMSServiceMock->shouldReceive('showTour')->zeroOrMoreTimes()->andReturn($this->tourXML);
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

    public function test_when_there_is_no_product_id_then_we_get_bad_request_response_and_missing_required_params_product_id_error_message(): void
    {
        $response = $this->post(
            self::AVAILABILIY_PATH,
            [
                'optionId' => self::VALID_OPTION_ID,
                'localDateStart' => self::VALID_LOCAL_DATE,
                'localDateEnd' => self::VALID_LOCAL_DATE,
            ],
            [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]
        );

        $response->assertBadRequest();
        $this->assertEquals(OctoResponse::ERROR_CODE_BAD_REQUEST, $response['error']);
        $this->assertEquals(AvailabilityCalendarService::ERROR_MESSAGE_MISSING_REQUIRED_PARAMS.'productId', $response['errorMessage']);
    }

    public function test_when_there_is_no_option_id_then_we_get_bad_request_response_and_missing_required_params_option_id_error_message(): void
    {
        $response = $this->post(
            self::AVAILABILIY_PATH,
            [
                'productId' => self::VALID_PRODUCT_ID,
                'localDateStart' => self::VALID_LOCAL_DATE,
                'localDateEnd' => self::VALID_LOCAL_DATE,
            ],
            [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]
        );

        $response->assertBadRequest();
        $this->assertEquals(OctoResponse::ERROR_CODE_BAD_REQUEST, $response['error']);
        $this->assertEquals(AvailabilityCalendarService::ERROR_MESSAGE_MISSING_REQUIRED_PARAMS.'optionId', $response['errorMessage']);
    }

    public function test_when_there_is_no_local_date_start_then_we_get_bad_request_response_and_missing_required_params_local_date_start_error_message(): void
    {
        $response = $this->post(
            self::AVAILABILIY_PATH,
            [
                'productId' => self::VALID_PRODUCT_ID,
                'optionId' => self::VALID_OPTION_ID,
                'localDateEnd' => self::VALID_LOCAL_DATE,
            ],
            [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]
        );

        $response->assertBadRequest();
        $this->assertEquals(OctoResponse::ERROR_CODE_BAD_REQUEST, $response['error']);
        $this->assertEquals(AvailabilityCalendarService::ERROR_MESSAGE_MISSING_REQUIRED_PARAMS.'localDateStart', $response['errorMessage']);
    }

    public function test_when_there_is_no_local_date_end_then_we_get_bad_request_response_and_missing_required_params_local_date_end_error_message(): void
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
        $this->assertEquals(OctoResponse::ERROR_CODE_BAD_REQUEST, $response['error']);
        $this->assertEquals(AvailabilityCalendarService::ERROR_MESSAGE_MISSING_REQUIRED_PARAMS.'localDateEnd', $response['errorMessage']);
    }

    public function test_when_we_send_invalid_product_id_then_we_get_bad_request_response_and_invalid_product_id_error(): void
    {
        $response = $this->post(
            self::AVAILABILIY_PATH,
            [
                'productId' => self::INVALID_PRODUCT_ID,
                'optionId' => self::VALID_OPTION_ID,
                'localDateStart' => self::VALID_LOCAL_DATE,
                'localDateEnd' => self::VALID_LOCAL_DATE,
            ],
            [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]
        );

        $responseData = $response->decodeResponseJson();
        $response->assertBadRequest();
        $this->assertEquals(OctoResponse::ERROR_CODE_INVALID_PRODUCT_ID, $responseData['error']);
        $this->assertEquals(OctoResponse::ERROR_MESSAGE_INVALID_PRODUCT_ID, $responseData['errorMessage']);
    }

    public function test_when_we_send_invalid_option_id_then_we_get_bad_request_response_and_invalid_option_id_error(): void
    {
        $response = $this->post(
            self::AVAILABILIY_PATH,
            [
                'productId' => self::VALID_PRODUCT_ID,
                'optionId' => self::INVALID_OPTION_ID,
                'localDateStart' => self::VALID_LOCAL_DATE,
                'localDateEnd' => self::VALID_LOCAL_DATE,
            ],
            [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]
        );

        $responseData = $response->decodeResponseJson();
        $response->assertBadRequest();
        $this->assertEquals(OctoResponse::ERROR_CODE_INVALID_OPTION_ID, $responseData['error']);
        $this->assertEquals(OctoResponse::ERROR_MESSAGE_INVALID_OPTION_ID, $responseData['errorMessage']);
    }

    public function test_when_we_send_invalid_local_date_start_then_we_get_bad_request_response_and_invalid_local_date_start_error_message(): void
    {
        $response = $this->post(
            self::AVAILABILIY_PATH,
            [
                'productId' => self::VALID_PRODUCT_ID,
                'optionId' => self::VALID_OPTION_ID,
                'localDateStart' => self::INVALID_LOCAL_DATE,
                'localDateEnd' => self::VALID_LOCAL_DATE,
            ],
            [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]
        );

        $response->assertBadRequest();
        $this->assertEquals(OctoResponse::ERROR_CODE_BAD_REQUEST, $response['error']);
        $this->assertEquals(AvailabilityCalendarService::ERROR_MESSAGE_LOCAL_DATE_START_INVALID, $response['errorMessage']);
    }

    public function test_when_we_send_invalid_local_date_end_then_we_get_bad_request_response_and_invalid_local_date_end_error_message(): void
    {
        $response = $this->post(
            self::AVAILABILIY_PATH,
            [
                'productId' => self::VALID_PRODUCT_ID,
                'optionId' => self::VALID_OPTION_ID,
                'localDateStart' => self::VALID_LOCAL_DATE,
                'localDateEnd' => self::INVALID_LOCAL_DATE,
            ],
            [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]
        );

        $response->assertBadRequest();
        $this->assertEquals(OctoResponse::ERROR_CODE_BAD_REQUEST, $response['error']);
        $this->assertEquals(AvailabilityCalendarService::ERROR_MESSAGE_LOCAL_DATE_END_INVALID, $response['errorMessage']);
    }

    public function test_when_we_send_correct_data_then_we_get_valid_availability_calendar_response(): void
    {

        $response = $this->post(
            self::AVAILABILIY_PATH,
            [
                'productId' => self::VALID_PRODUCT_ID,
                'optionId' => self::VALID_OPTION_ID,
                'localDateStart' => self::VALID_LOCAL_DATE,
                'localDateEnd' => self::VALID_LOCAL_DATE,
            ],
            [self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS]
        );

        $responseData = $response->decodeResponseJson();
        $response->assertOk();

        $this->assertNotEmpty($responseData);
    }
}
