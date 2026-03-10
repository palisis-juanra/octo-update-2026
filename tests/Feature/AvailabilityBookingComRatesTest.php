<?php

namespace Tests\Feature;

use App\Http\Requests\OctoRequest;
use App\Http\Responses\OctoResponse;
use App\Services\JSONLogService;
use App\Services\TourCMSService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Mockery;
use SimpleXMLElement;
use Tests\FeatureTestCase;

class AvailabilityBookingComRatesTest extends FeatureTestCase
{
    use RefreshDatabase;

    public const AUTH_HEADER_NAME = 'Authorization';
    public const OCTO_VALID_PATTERN_CREDENTIALS = 'Bearer 1|142|abc';

    public const VALID_PRODUCT_ID = 'TE_1_67|142';
    public const VALID_OPTION_ID = 'START_TIME';
    public const VALID_LOCAL_DATE = '2024-11-30';

    public const CAPABILITIES_HEADER_NAME = 'OCapabilities';
    public const CAPABILITY_BOOKINGCOM_RATES = 'bookingcom/rates';

    public SimpleXMLElement $showChannelXML;
    public SimpleXMLElement $showTourXML;
    public SimpleXMLElement $showTourDeparturesOneDayXML;
    public SimpleXMLElement $checkAvailXML;
    public SimpleXMLElement $tourPromotionsXML;

    public function setUp(): void
    {
        parent::setUp();

        $this->showChannelXML = simplexml_load_file('tests/TourCMSResponses/showChannel.xml');
        $this->showTourXML = simplexml_load_file('tests/TourCMSResponses/showTour.xml');
        $this->showTourDeparturesOneDayXML = simplexml_load_file('tests/TourCMSResponses/showTourDeparturesWithNetRates.xml');
        $this->checkAvailXML = simplexml_load_file('tests/TourCMSResponses/checkAvailabilityWithNetRates.xml');

        /**
         * OPEN 0%
         * GENIUS1 10%
         * GENIUS2 20%
         */
        $this->tourPromotionsXML = simplexml_load_file('tests/TourCMSResponses/getTourPromotions.xml');

        $this->showTourXML->tour->distribution_identifier = 'TE_1_67';
        $this->showTourXML->tour->channel_id = 142;

        App::bind(JSONLogService::class, function ($app) {
            $jsonLogServiceMock = Mockery::mock(JSONLogService::class)->makePartial();
            $jsonLogServiceMock->shouldReceive('info')->zeroOrMoreTimes()->andReturnNull();
            $jsonLogServiceMock->shouldReceive('error')->zeroOrMoreTimes()->andReturnNull();
            $jsonLogServiceMock->shouldReceive('getLogId')->zeroOrMoreTimes()->andReturn('');
            return $jsonLogServiceMock;
        });
    }

    public function test_whenBookingComRatesCapabilityAndRateIdIsInvalid_thenReturnAnInvalidRateIdException(): void
    {
        $this->bindTourCMSService();

        $response = $this->post(
            '/availability',
            [
                'productId' => self::VALID_PRODUCT_ID,
                'optionId' => self::VALID_OPTION_ID,
                'localDate' => self::VALID_LOCAL_DATE,
                'rateId' => 'PEPE', // there is no promotion with id PEPE
            ],
            [
                self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS,
                OctoRequest::CAPABILITIES_HEADER => OctoRequest::CAPABILITIES_BOOKINGCOM_RATES,
            ]
        );

        $response->assertBadRequest();
        $data = $response->json();
        
        $this->assertEquals(OctoResponse::ERROR_CODE_INVALID_RATE_ID, $data['error'], "Error code must be " . OctoResponse::ERROR_CODE_INVALID_RATE_ID);
        $this->assertEquals(OctoResponse::ERROR_MESSAGE_INVALID_RATE_ID, $data['errorMessage'], "Error message must be " . OctoResponse::ERROR_CODE_INVALID_RATE_ID);
    }

    public function test_whenRateIdIsSent_thenPricingAppliesDiscountFromThatPromotion(): void
    {
        $this->bindTourCMSService();

        $response = $this->post(
            '/availability',
            [
                'productId' => self::VALID_PRODUCT_ID,
                'optionId' => self::VALID_OPTION_ID,
                'localDate' => self::VALID_LOCAL_DATE,
                'rateId' => 'GENIUS1',
            ],
            [
                self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS,
                OctoRequest::CAPABILITIES_HEADER => OctoRequest::CAPABILITIES_PRICING . "," . OctoRequest::CAPABILITIES_BOOKINGCOM_RATES,
            ]
        );

        $response->assertOk();

        $responseData = $response->json();
        $availabilities = $responseData['availabilities'] ?? $responseData;
        $firstAvailability = $availabilities[0];

        $this->assertArrayHasKey('pricing', $firstAvailability);

        $pricing = $firstAvailability['pricing'];

        $this->assertEquals('GENIUS1', $pricing['rateId']);

        /**
         * - checkAvailability.xml returns total_price = 50.00 y net_price = 39.75
         * - GENIUS1 have discount = 10%
         *
         * Then:
         * retail = 5000 -> 4500
         * net = 3975 -> 3578
         *
         */
        $this->assertEquals(4500, $pricing['retail']);
        $this->assertEquals(3578, $pricing['net']);

        $matchingRates = array_values(array_filter(
            $pricing['rates'],
            fn (array $rate) => $rate['id'] === 'GENIUS1'
        ));

        $this->assertNotEmpty($matchingRates);
        $this->assertEquals(4500, $matchingRates[0]['retail']);
        $this->assertEquals(3578, $matchingRates[0]['net']);

        /* Check unit pricing */
        $firstUnitPricing = $firstAvailability['unitPricing'][0];

        $this->assertEquals('GENIUS1', $firstUnitPricing['rateId']);

        $unitMatchingRates = array_values(array_filter(
            $firstUnitPricing['rates'],
            fn (array $rate) => $rate['id'] === 'GENIUS1'
        ));

        $this->assertNotEmpty($unitMatchingRates);
        
    }

    public function test_whenBookingComRatesCapabilityIsSent_thenResponseContainsRateIdAndRates(): void
    {
        $this->bindTourCMSService();

        $response = $this->post(
            '/availability',
            [
                'productId' => self::VALID_PRODUCT_ID,
                'optionId' => self::VALID_OPTION_ID,
                'localDate' => self::VALID_LOCAL_DATE,
            ],
            [
                self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS,
                OctoRequest::CAPABILITIES_HEADER => OctoRequest::CAPABILITIES_PRICING . "," . OctoRequest::CAPABILITIES_BOOKINGCOM_RATES,
            ]
        );

        $response->assertOk();

        $responseData = $response->json();

        $this->assertIsArray($responseData);
        $this->assertNotEmpty($responseData['availabilities'] ?? $responseData);

        $availabilities = $responseData['availabilities'] ?? $responseData;
        $firstAvailability = $availabilities[0];

        $this->assertArrayHasKey('pricing', $firstAvailability);
        $this->assertArrayHasKey('rateId', $firstAvailability['pricing']);
        $this->assertArrayHasKey('rates', $firstAvailability['pricing']);

        $this->assertNull($firstAvailability['pricing']['rateId']);
        $this->assertIsArray($firstAvailability['pricing']['rates']);
        $this->assertNotEmpty($firstAvailability['pricing']['rates']);

        $firstRate = $firstAvailability['pricing']['rates'][0];
        $this->assertArrayHasKey('id', $firstRate);
        $this->assertArrayHasKey('retail', $firstRate);
        $this->assertArrayHasKey('net', $firstRate);

        $firstUnitPricing = $firstAvailability['unitPricing'][0];

        $this->assertArrayHasKey('rateId', $firstUnitPricing);
        $this->assertArrayHasKey('rates', $firstUnitPricing);
        $this->assertNull($firstUnitPricing['rateId']);
        $this->assertIsArray($firstUnitPricing['rates']);
        
    }

    public function test_whenNoRateIdIsSent_thenPricingKeepsNormalPrice(): void
{
    $this->bindTourCMSService();

    $response = $this->post(
        '/availability',
        [
            'productId' => self::VALID_PRODUCT_ID,
            'optionId' => self::VALID_OPTION_ID,
            'localDate' => self::VALID_LOCAL_DATE,
        ],
        [
            self::AUTH_HEADER_NAME => self::OCTO_VALID_PATTERN_CREDENTIALS,
            OctoRequest::CAPABILITIES_HEADER => OctoRequest::CAPABILITIES_PRICING . "," . OctoRequest::CAPABILITIES_BOOKINGCOM_RATES,
        ]
    );

    $response->assertOk();

    $responseData = $response->json();
    $availabilities = $responseData['availabilities'] ?? $responseData;
    $firstAvailability = $availabilities[0];
    $pricing = $firstAvailability['pricing'];

    /**
     * Base values from fixture:
     * retail = 5000
     * net = 3975
     *
     * Without rateId, main pricing must remain unchanged.
     */
    $this->assertNull($pricing['rateId']);
    $this->assertEquals(5000, $pricing['retail']);
    $this->assertEquals(3975, $pricing['net']);

    /**
     * Rates list is still present, but main pricing should not be discounted.
     */
    $this->assertIsArray($pricing['rates']);
    $this->assertNotEmpty($pricing['rates']);
}

    /* PROTECTED / PRIVATE METHODS */
    private function bindTourCMSService(): void
    {
        App::bind(TourCMSService::class, function ($app) {
            $tourCMSServiceMock = Mockery::mock(TourCMSService::class)->makePartial();

            $tourCMSServiceMock->shouldReceive('showChannel')->zeroOrMoreTimes()->andReturn($this->showChannelXML);
            $tourCMSServiceMock->shouldReceive('showTour')->zeroOrMoreTimes()->andReturn($this->showTourXML);
            $tourCMSServiceMock->shouldReceive('showTourDepartures')->zeroOrMoreTimes()->andReturn($this->showTourDeparturesOneDayXML);

            $tourCMSServiceMock->shouldReceive('checkAvailability')
                ->zeroOrMoreTimes()
                ->andReturn($this->checkAvailXML);

            $tourCMSServiceMock->shouldReceive('getTourPromotions')
                ->zeroOrMoreTimes()
                ->andReturn($this->tourPromotionsXML);

            $tourCMSServiceMock->shouldReceive('getArrayFromXmlNode')
                ->zeroOrMoreTimes()
                ->andReturnUsing(function ($xmlNode, $childNodeName) {
                    $items = [];
                    if (empty($xmlNode)) {
                        return $items;
                    }

                    foreach ($xmlNode->{$childNodeName} as $child) {
                        $items[] = $child;
                    }

                    return $items;
                });

            return $tourCMSServiceMock;
        });
    }
}