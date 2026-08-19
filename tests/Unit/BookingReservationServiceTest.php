<?php

namespace App\Tests;

use App\Exceptions\NoAvailabilityException;
use App\Services\BookingReservationService;
use SimpleXMLElement;
use Tests\UnitTestCase;

class BookingReservationServiceTest extends UnitTestCase
{
    public function test_we_generate_correct_rates_query_string_from_unit_items(): void
    {
        $mock = $this->getMockBuilder(BookingReservationService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $singleUnitItemsOneTime = [
            [
                'unitId' => 'TE_1_67|142|r1',
            ],
        ];

        $multipleUnitItemsOneTime = [
            [
                'unitId' => 'TE_1_67|142|r1',
            ],
            [
                'unitId' => 'TE_1_67|142|r2',
            ],
        ];

        $multipleUnitItemsMultiplesTimes = [
            [
                'unitId' => 'TE_1_67|142|r1',
            ],
            [
                'unitId' => 'TE_1_67|142|r1',
            ],
            [
                'unitId' => 'TE_1_67|142|r2',
            ],
            [
                'unitId' => 'TE_1_67|142|r2',
            ],
            [
                'unitId' => 'TE_1_67|142|r2',
            ],
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

    public function test_we_generate_correct_check_avail_query_string(): void
    {
        $mock = $this->getMockBuilder(BookingReservationService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $date = '2024-12-10';
        $unitItems = [
            [
                'unitId' => 'TE_1_67|142|r1',
            ],
            [
                'unitId' => 'TE_1_67|142|r1',
            ],
            [
                'unitId' => 'TE_1_67|142|r2',
            ],
        ];

        $expectedQueryString = 'date=2024-12-10&r1=2&r2=1';
        $queryString = $mock->generateCheckAvailQueryString($date, $unitItems);

        $this->assertEquals($expectedQueryString, $queryString);
    }

    public function test_we_find_component_by_departure_id(): void
    {
        $component1 = new SimpleXMLElement('<component />');
        $component1->addChild('date_id', 1);

        $component2 = new SimpleXMLElement('<component />');
        $component2->addChild('date_id', 2);

        $components = [$component1, $component2];

        $mock = $this->getMockBuilder(BookingReservationService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $foundComponent = $mock->getComponentByDepartureId($components, $component1->date_id);

        $this->assertEquals($component1, $foundComponent);
    }

    public function test_when_we_cant_find_component_by_departure_id_then_we_throw_an_exception(): void
    {
        $component1 = new SimpleXMLElement('<component />');
        $component1->addChild('date_id', 1);

        $component2 = new SimpleXMLElement('<component />');
        $component2->addChild('date_id', 2);

        $components = [$component1, $component2];

        $mock = $this->getMockBuilder(BookingReservationService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $this->expectException(NoAvailabilityException::class);
        $mock->getComponentByDepartureId($components, 3);
    }

    public function test_when_we_generate_booking_data_without_uuid_then_we_get_correct_xml(): void
    {
        $mock = $this->getMockBuilder(BookingReservationService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $unitItems = [
            [
                'unitId' => 'TE_1_67|142|r1',
            ],
            [
                'unitId' => 'TE_1_67|142|r2',
            ],
        ];
        $totalCustomers = 2;
        $componentKey = 'abc123';

        $startNewBookingXML = $mock->getBookingDataForStartNewBooking($unitItems, $componentKey);

        $this->assertEquals(1, (int) $startNewBookingXML->manage_uuid);
        $this->assertEquals($componentKey, (string) $startNewBookingXML->components->component->component_key);
        $this->assertEquals($totalCustomers, (int) $startNewBookingXML->total_customers);
    }

    public function test_when_we_generate_booking_data_with_uuid_then_we_get_correct_xml(): void
    {
        $mock = $this->getMockBuilder(BookingReservationService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $unitItems = [
            [
                'unitId' => 'TE_1_67|142|r1',
            ],
            [
                'unitId' => 'TE_1_67|142|r2',
            ],
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
