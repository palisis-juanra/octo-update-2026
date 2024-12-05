<?php

namespace App\Tests;

use App\Services\BookingReservationService;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use App\Exceptions\NoAvailabilityException;

class BookingReservationServiceTest extends TestCase
{
    public function test_weGenerateCorrectRatesQueryStringFromUnitItems()
    {
        $mock = $this->getMockBuilder(BookingReservationService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        
        $singleUnitItemsOneTime = [
            [
                'unitId' => 'TE_1_67|r1'
            ]
        ];
    
        $multipleUnitItemsOneTime = [
            [
                'unitId' => 'TE_1_67|r1'
            ],
            [
                'unitId' => 'TE_1_67|r2'
            ]
        ];
    
        $multipleUnitItemsMultiplesTimes = [
            [
                'unitId' => 'TE_1_67|r1'
            ],
            [
                'unitId' => 'TE_1_67|r1'
            ],
            [
                'unitId' => 'TE_1_67|r2'
            ],
            [
                'unitId' => 'TE_1_67|r2'
            ],
            [
                'unitId' => 'TE_1_67|r2'
            ]
        ];

        $singleOneRatesQueryString = $mock->generateRatesQueryStringFromUnitItems($singleUnitItemsOneTime);
        $multipleOneRatesQueryString = $mock->generateRatesQueryStringFromUnitItems($multipleUnitItemsOneTime);
        $multipleMultiplRatesQueryString = $mock->generateRatesQueryStringFromUnitItems($multipleUnitItemsMultiplesTimes);

        $singleOneExpected = 'r1=1';
        $multipleOneExpected = 'r1=1&r2=1';
        $multipleMultipleExpected = 'r1=2&r2=3';

        $this->assertEquals($singleOneExpected, $singleOneRatesQueryString);
        $this->assertEquals($multipleOneExpected, $multipleOneRatesQueryString);
        $this->assertEquals($multipleMultipleExpected, $multipleMultiplRatesQueryString);
    }

    public function test_weGenerateCorrectCheckAvailQueryString()
    {
        $mock = $this->getMockBuilder(BookingReservationService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        
        $date = '2024-12-10';
        $unitItems = [
            [
                'unitId' => 'TE_1_67|r1'
            ],
            [
                'unitId' => 'TE_1_67|r1'
            ],
            [
                'unitId' => 'TE_1_67|r2'
            ]
        ];

        $expectedQueryString = 'date=2024-12-10&r1=2&r2=1';
        $queryString = $mock->generateCheckAvailQueryString($date, $unitItems);
        
        $this->assertEquals($expectedQueryString, $queryString);
    }

    public function test_weFindComponentByDepartureId()
    {
        $component1 = new SimpleXMLElement("<component />");
        $component1->addChild('date_id', 1);
    
        $component2 = new SimpleXMLElement("<component />");
        $component2->addChild('date_id', 2);

        $components = [$component1, $component2];

        $mock = $this->getMockBuilder(BookingReservationService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        
        $foundComponent = $mock->getComponentByDepartureId($components, $component1->date_id);

        $this->assertEquals($component1, $foundComponent);
    }

    public function test_whenWeCantFindComponentByDepartureId_thenWeThrowAnException()
    {
        $component1 = new SimpleXMLElement("<component />");
        $component1->addChild('date_id', 1);
    
        $component2 = new SimpleXMLElement("<component />");
        $component2->addChild('date_id', 2);

        $components = [$component1, $component2];

        $mock = $this->getMockBuilder(BookingReservationService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        
        $this->expectException(NoAvailabilityException::class);
        $mock->getComponentByDepartureId($components, 3);
    }

    public function test_whenWeGenerateBookingDataWithoutUuid_thenWeGetCorrectXML()
    {
        $mock = $this->getMockBuilder(BookingReservationService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $unitItems = [
            [
                'unitId' => 'TE_1_67|r1'
            ],
            [
                'unitId' => 'TE_1_67|r2'
            ]
        ];
        $totalCustomers = 2;
        $componentKey = 'abc123';
        
        $startNewBookingXML = $mock->getBookingDataForStartNewBooking($unitItems, $componentKey);

        $this->assertEquals(1, (int) $startNewBookingXML->manage_uuid);
        $this->assertEquals($componentKey, (string) $startNewBookingXML->components->component->component_key);
        $this->assertEquals($totalCustomers, (int) $startNewBookingXML->total_customers);  
    }

    public function test_whenWeGenerateBookingDataWithUuid_thenWeGetCorrectXML()
    {
        $mock = $this->getMockBuilder(BookingReservationService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        
        $unitItems = [
            [
                'unitId' => 'TE_1_67|r1'
            ],
            [
                'unitId' => 'TE_1_67|r2'
            ]
        ];
        $totalCustomers = 2;
        $componentKey = 'abc123';
        $uuid = '5f981c36-d5ac-49a9-bbd3-ebf2ccdb0229';
        
        $startNewBookingXML = $mock->getBookingDataForStartNewBooking($unitItems, $componentKey, $uuid);

        $this->assertEquals($uuid, $startNewBookingXML->booking_uuid);
        $this->assertEquals(1, (int) $startNewBookingXML->manage_uuid);
        $this->assertEquals($componentKey, (string) $startNewBookingXML->components->component->component_key);
        $this->assertEquals($totalCustomers, (int) $startNewBookingXML->total_customers);
    }

}