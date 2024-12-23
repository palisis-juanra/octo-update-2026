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

    public function setUp(): void
    {
        parent::setUp();
        $this->datesAndDealsString = file_get_contents('./tests/TourCMSResponses/datesAndDeals.xml');
        $this->datesAndDealsNoDatesString = file_get_contents('./tests/TourCMSResponses/datesAndDealsNoDates.xml');
        $this->datesAndDealsXML = simplexml_load_string($this->datesAndDealsString);
        $this->datesAndDealsNoDatesXML = simplexml_load_string($this->datesAndDealsNoDatesString);
    }

    public function test_getAvailabilities_whenCallWithValidData_thenWeGetValidAvailabilitiesArray()
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

    public function test_fetchDatesAndDealsFromAPI_whenCallWithValidData_thenWeGetValidDatesAndDealsArray()
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
        for ($i = 0; $i < count($this->datesAndDealsXML->dates_and_prices->date); $i++) {
            $this->assertEquals($this->datesAndDealsXML->dates_and_prices->date[$i], $datesAndDeals[$i]);
        }
        $this->assertNotEmpty($datesAndDeals);
        $this->assertCount(self::DATES_AND_DEALS_XML_COUNT, $datesAndDeals);
    }

    public function test_fetchDatesAndDealsFromAPI_whenAPIReturnsNoDates_thenWeGetEmptyArray()
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

        $availabilityCalendarRequestMock->tourId = "143";
        $availabilityCalendarRequestMock->optionId = "SINGLE";
        $availabilityCalendarRequestMock->localDateStart = "2024-11-21";
        $availabilityCalendarRequestMock->localDateEnd = "2024-11-28";

        // When
        $datesAndDeals = $availabilityCalendarRequestMock->fetchDatesAndDealsFromAPI($tourCMSService);

        // Then
        $this->assertIsArray($datesAndDeals);
        $this->assertEmpty($datesAndDeals);
    }

    public function test_getAvailabilitiesFromDatesAndDeals_whenCallWithValidData_thenWeGetValidAvailabilitiesArray()
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

    public function test_checkSpacesRemaining_whenCallWithExceedingUnitQuantity_thenShouldReturnFalse()
    {
        // Given
        $units = [
            ['id' => 'TE_1_184|r1', 'quantity' => 27], ['id' => 'TE_1_184|r2', 'quantity' => 5]
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