<?php

namespace Tests\Unit;

use App\Models\Availability\AvailabilityCalendarRequest;
use App\Services\TourCMSService;
use PHPUnit\Framework\MockObject\MockObject;
use SimpleXMLElement;
use Tests\UnitTestCase;

class AvailabilityCalendarRequestTest extends UnitTestCase
{
    const DATES_AND_DEALS_XML_COUNT = 6;

    public string $datesAndDealsString;

    public string $datesAndDealsNoDatesString;

    public SimpleXMLElement $datesAndDealsXML;

    public SimpleXMLElement $datesAndDealsNoDatesXML;

    protected function setUp(): void
    {
        parent::setUp();
        $this->datesAndDealsString = file_get_contents('./tests/TourCMSResponses/datesAndDeals.xml');
        $this->datesAndDealsNoDatesString = file_get_contents('./tests/TourCMSResponses/datesAndDealsNoDates.xml');
        $this->datesAndDealsXML = simplexml_load_string($this->datesAndDealsString);
        $this->datesAndDealsNoDatesXML = simplexml_load_string($this->datesAndDealsNoDatesString);
    }

    public function test_get_availabilities_when_call_with_valid_data_then_we_get_valid_availabilities_array()
    {
        // Given
        $datesAndDealsData = [];
        foreach ($this->datesAndDealsXML->dates_and_prices->date as $date) {
            $datesAndDealsData[] = $date;
        }

        $availabilityCalendarRequestMock = $this->getMockBuilder(AvailabilityCalendarRequest::class)
            ->onlyMethods(['fetchDatesAndDealsFromAPI'])
            ->disableOriginalConstructor()
            ->getMock();
        $availabilityCalendarRequestMock->method('fetchDatesAndDealsFromAPI')->willReturn($datesAndDealsData);
        $availabilityCalendarRequestMock->setUnits([]);

        // When
        $availabilities = $availabilityCalendarRequestMock->getAvailabilities($this->mockTourCMSService());

        // Then
        $this->assertIsArray($availabilities);
        $this->assertNotEmpty($availabilities);
        $this->assertCount(self::DATES_AND_DEALS_XML_COUNT, $availabilities);
    }

    public function test_fetch_dates_and_deals_from_ap_i_when_call_with_valid_data_then_we_get_valid_dates_and_deals_array()
    {
        // Given
        $availabilityCalendarRequestMock = $this->getMockBuilder(AvailabilityCalendarRequest::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $tourCMSService = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods(['showTourDatesAndDeals'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSService->method('showTourDatesAndDeals')->willReturn($this->datesAndDealsXML);

        $availabilityCalendarRequestMock->tourId = '143';
        $availabilityCalendarRequestMock->optionId = 'SINGLE';
        $availabilityCalendarRequestMock->localDateStart = '2024-11-21';
        $availabilityCalendarRequestMock->localDateEnd = '2024-11-28';

        // When
        $datesAndDeals = $availabilityCalendarRequestMock->fetchDatesAndDealsFromAPI($tourCMSService);

        // Then
        $this->assertIsArray($datesAndDeals);
        for ($i = 0; $i < count($this->datesAndDealsXML->dates_and_prices->date); $i++) {
            $this->assertEquals($this->datesAndDealsXML->dates_and_prices->date[$i], $datesAndDeals[$i]);
        }
        $this->assertNotEmpty($datesAndDeals);
        $this->assertCount(self::DATES_AND_DEALS_XML_COUNT, $datesAndDeals);
    }

    public function test_fetch_dates_and_deals_from_ap_i_when_api_returns_no_dates_then_we_get_empty_array()
    {
        // Given
        $availabilityCalendarRequestMock = $this->getMockBuilder(AvailabilityCalendarRequest::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $tourCMSService = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods(['showTourDatesAndDeals'])
            ->disableOriginalConstructor()
            ->getMock();
        $tourCMSService->method('showTourDatesAndDeals')->willReturn($this->datesAndDealsNoDatesXML);

        $availabilityCalendarRequestMock->tourId = '143';
        $availabilityCalendarRequestMock->optionId = 'SINGLE';
        $availabilityCalendarRequestMock->localDateStart = '2024-11-21';
        $availabilityCalendarRequestMock->localDateEnd = '2024-11-28';

        // When
        $datesAndDeals = $availabilityCalendarRequestMock->fetchDatesAndDealsFromAPI($tourCMSService);

        // Then
        $this->assertIsArray($datesAndDeals);
        $this->assertEmpty($datesAndDeals);
    }

    public function test_get_availabilities_from_dates_and_deals_when_call_with_valid_data_then_we_get_valid_availabilities_array()
    {
        // Given
        $datesAndDealsData = [];
        foreach ($this->datesAndDealsXML->dates_and_prices->date as $date) {
            $datesAndDealsData[] = $date;
        }

        $availabilityCalendarRequestMock = $this->getMockBuilder(AvailabilityCalendarRequest::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        $availabilityCalendarRequestMock->setUnits([]);

        // When
        $availabilities = $availabilityCalendarRequestMock->getAvailabilitiesFromDatesAndDeals($datesAndDealsData);

        // Then
        $this->assertIsArray($availabilities);
        $this->assertNotEmpty($availabilities);
        $this->assertCount(self::DATES_AND_DEALS_XML_COUNT, $availabilities);
    }

    public function test_check_spaces_remaining_when_call_with_exceeding_unit_quantity_then_should_return_false()
    {
        // Given
        $units = [
            ['id' => 'TE_1_184|r1', 'quantity' => 27], ['id' => 'TE_1_184|r2', 'quantity' => 5],
        ];

        $availabilityCalendarRequestMock = $this->getMockBuilder(AvailabilityCalendarRequest::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        $availabilityCalendarRequestMock->setUnits($units);

        // When
        $isAvailable = $availabilityCalendarRequestMock->checkSpacesRemaining($this->datesAndDealsXML->dates_and_prices->date[0]);

        // Then
        $this->assertEquals(false, $isAvailable);
    }

    protected function mockTourCMSService(): TourCMSService|MockObject
    {
        $tourCMSService = $this->getMockBuilder(TourCMSService::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        return $tourCMSService;
    }
}
