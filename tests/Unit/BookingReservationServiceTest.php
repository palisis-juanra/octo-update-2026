<?php

namespace App\Tests;

use App\Services\BookingReservationService;
use PHPUnit\Framework\TestCase;

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

}