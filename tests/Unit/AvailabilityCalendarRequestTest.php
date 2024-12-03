<?php

namespace Tests\Unit;

use App\Models\Availability\AvailabilityCalendarRequest;
use App\Services\TourCMSService;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;
use SimpleXMLElement;

class AvailabilityCalendarRequestTest extends TestCase
{
    const DATES_AND_DEALS_XML_COUNT = 6;
    public string $datesAndDealsString;
    public SimpleXMLElement $datesAndDealsXML;

    public function setUp(): void
    {
        parent::setUp();
        $this->datesAndDealsString = file_get_contents('./tests/TourCMSResponses/datesAndDeals.xml');
        $this->datesAndDealsXML = simplexml_load_string($this->datesAndDealsString);
    }

    public function test_whenCallGetAvailabilities_thenWeGetValidStructure()
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

        // When
        $availabilities = $availabilityCalendarRequestMock->getAvailabilities($this->mockTourCMSService());

        // Then
        $this->assertIsArray($availabilities);
        $this->assertNotEmpty($availabilities);
        $this->assertCount(self::DATES_AND_DEALS_XML_COUNT, $availabilities);
    }

    public function test_whenCallFetchDatesAndDealsFromAPI_thenWeGetValidStructure()
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

        $availabilityCalendarRequestMock->tourId = "143";
        $availabilityCalendarRequestMock->optionId = "SINGLE";
        $availabilityCalendarRequestMock->localDateStart = "2024-11-21";
        $availabilityCalendarRequestMock->localDateEnd = "2024-11-28";

        // When
        $datesAndDeals = $availabilityCalendarRequestMock->fetchDatesAndDealsFromAPI($tourCMSService);

        // Then
        $this->assertIsArray($datesAndDeals);
        $this->assertNotEmpty($datesAndDeals);
        $this->assertCount(self::DATES_AND_DEALS_XML_COUNT, $datesAndDeals);
    }

    public function test_whenCallGetAvailabilitiesFromDatesAndDeals_thenWeGetValidStructure()
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

        // When
        $availabilities = $availabilityCalendarRequestMock->getAvailabilitiesFromDatesAndDeals($datesAndDealsData);

        // Then
        $this->assertIsArray($availabilities);
        $this->assertNotEmpty($availabilities);
        $this->assertCount(self::DATES_AND_DEALS_XML_COUNT, $availabilities);
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