<?php

namespace Tests\Unit;

use App\Models\ProductMapping;
use App\Services\JSONLogService;
use App\Services\ProductMappingFactory;
use App\Services\ProductService;
use SimpleXMLElement;
use Tests\UnitTestCase;

class ProductMappingFactoryTest extends UnitTestCase
{
    public ProductMappingFactory $productMappingFactory;

    public function setUp(): void
    {
        parent::setUp();
        $this->productMappingFactory = new ProductMappingFactory;
    }

        public function test_whenTourIsMappedBySingleDeparturePerDayAndDontHaveStartTime_thenWeGetCorrectMappingWithDefaultStartTime(): void
    {       
        $showTourResponseXML = simplexml_load_string(file_get_contents('./tests/TourCMSResponses/TourDepartureStructure/SINGLE.xml'));
        $tourData = $showTourResponseXML->tour;
        unset($tourData->tour_departure_structure->start_times);

        $expectedProductOptions = [
            new ProductMapping(
                ProductService::MAPPING_STRUCTURE_TYPE_SINGLE,
                (string) $tourData->tour_name_long,
                "",
                ['09:00']
            )
        ];

        $productMappings = $this->productMappingFactory->create($tourData);
        $this->assertEquals($expectedProductOptions, $productMappings);
    }

    public function test_whenTourIsMappedBySingleDeparturePerDay_thenWeGetCorrectMapping(): void
    {            
        $showTourResponseXML = simplexml_load_string(file_get_contents('./tests/TourCMSResponses/TourDepartureStructure/SINGLE.xml'));
        $tourData = $showTourResponseXML->tour;

        $expectedProductOptions = [
            new ProductMapping(
                ProductService::MAPPING_STRUCTURE_TYPE_SINGLE,
                (string) $tourData->tour_name_long,
                "",
                ['11:00', '13:00']
            )
        ];

        $productMappings = $this->productMappingFactory->create($tourData);
        $this->assertEquals($expectedProductOptions, $productMappings);
    }

    public function test_whenTourIsMappedByStartTime_thenWeGetCorrectMappings(): void
    {            
        $showTourResponseXML = simplexml_load_string(file_get_contents('./tests/TourCMSResponses/TourDepartureStructure/START_TIME.xml'));
        $tourData = $showTourResponseXML->tour;

        $expectedProductOptions = [
            new ProductMapping(
                ProductService::MAPPING_STRUCTURE_TYPE_START_TIME,
                "",
                "13:00",
                ['13:00']
            ),
            new ProductMapping(
                ProductService::MAPPING_STRUCTURE_TYPE_START_TIME,
                "",
                "15:00",
                ['15:00']
            ),
            new ProductMapping(
                ProductService::MAPPING_STRUCTURE_TYPE_START_TIME,
                "",
                "17:00",
                ['17:00']
            )
        ];

        $productMappings = $this->productMappingFactory->create($tourData);
        $this->assertEquals($expectedProductOptions, $productMappings);
    }

    public function test_whenTourIsMappedBySupplierNote_thenWeGetCorrectMappings(): void
    {            
        $showTourResponseXML = simplexml_load_string(file_get_contents('./tests/TourCMSResponses/TourDepartureStructure/SUPPLIER_NOTE.xml'));
        $tourData = $showTourResponseXML->tour;

        $expectedProductOptions = [
            new ProductMapping(
                ProductService::MAPPING_STRUCTURE_TYPE_SUPPLIER_NOTE,
                "",
                "",
                ['00:00']
            ),
            new ProductMapping(
                ProductService::MAPPING_STRUCTURE_TYPE_SUPPLIER_NOTE,
                "",
                'TEST_NOTE_1',
                ['00:00']
            ),
            new ProductMapping(
                ProductService::MAPPING_STRUCTURE_TYPE_SUPPLIER_NOTE,
                "",
                'TEST_NOTE_2',
                ['00:00']
            ),
        ];

        $productMappings = $this->productMappingFactory->create($tourData);
        $this->assertEquals($expectedProductOptions, $productMappings);
    }

    public function test_whenTourIsMappedByDepartureCode_thenWeGetCorrectMappings(): void
    {
        $showTourResponseXML = simplexml_load_string(file_get_contents('./tests/TourCMSResponses/TourDepartureStructure/DEPARTURE_CODE.xml'));
        $tourData = $showTourResponseXML->tour;

        $expectedProductOptions = [
            new ProductMapping(
                ProductService::MAPPING_STRUCTURE_TYPE_DEPARTURE_CODE,
                "",
                'ABC',
                ['00:00']
            ),
            new ProductMapping(
                ProductService::MAPPING_STRUCTURE_TYPE_DEPARTURE_CODE,
                "",
                '123',
                ['00:00']
            ),
            new ProductMapping(
                ProductService::MAPPING_STRUCTURE_TYPE_DEPARTURE_CODE,
                "",
                'xyz',
                ['00:00']
            ),
        ];

        $productMappings = $this->productMappingFactory->create($tourData);
        $this->assertEquals($expectedProductOptions, $productMappings);
    }

    public function test_whenTourIsMappedBySupplierNotePlusStartTime_thenWeGetOnlyNonPartialAndCorrectMappings(): void
    {   
        $showTourResponseXML = simplexml_load_string(file_get_contents('./tests/TourCMSResponses/TourDepartureStructure/SUPPLIER_NOTE_PLUS_START_TIME.xml'));
        $tourData = $showTourResponseXML->tour;

        $expectedProductOptions = [
            new ProductMapping(
                ProductService::MAPPING_STRUCTURE_TYPE_SUPPLIER_NOTE_PLUS_START_TIME,
                "",
                'SUP_NOTE_TEST',
                ['17:00', '13:00']
            )
        ];

        $productMappings = $this->productMappingFactory->create($tourData);
        $this->assertEquals($expectedProductOptions, $productMappings);
    }
}