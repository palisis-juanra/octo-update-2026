<?php

namespace Tests\Unit;

use App\Models\ProductMapping;
use App\Services\ProductMappingFactory;
use App\Services\ProductService;
use Tests\UnitTestCase;

class ProductMappingFactoryTest extends UnitTestCase
{
    public ProductMappingFactory $productMappingFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->productMappingFactory = new ProductMappingFactory;
    }

    public function test_when_tour_is_mapped_by_single_departure_per_day_and_dont_have_start_time_then_we_get_correct_mapping_with_default_start_time(): void
    {
        $showTourResponseXML = simplexml_load_string(file_get_contents('./tests/TourCMSResponses/TourDepartureStructure/SINGLE.xml'));
        $tourData = $showTourResponseXML->tour;
        unset($tourData->tour_departure_structure->start_times);

        $expectedProductOptions = [
            new ProductMapping(
                ProductService::MAPPING_STRUCTURE_TYPE_SINGLE,
                '',
                '',
                ['09:00']
            ),
        ];

        $productMappings = $this->productMappingFactory->create($tourData);
        $this->assertEquals($expectedProductOptions, $productMappings);
    }

    public function test_when_tour_is_mapped_by_single_departure_per_day_then_we_get_correct_mapping(): void
    {
        $showTourResponseXML = simplexml_load_string(file_get_contents('./tests/TourCMSResponses/TourDepartureStructure/SINGLE.xml'));
        $tourData = $showTourResponseXML->tour;

        $expectedProductOptions = [
            new ProductMapping(
                ProductService::MAPPING_STRUCTURE_TYPE_SINGLE,
                '',
                '',
                ['11:00', '13:00']
            ),
        ];

        $productMappings = $this->productMappingFactory->create($tourData);
        $this->assertEquals($expectedProductOptions, $productMappings);
    }

    public function test_when_tour_is_mapped_by_start_time_then_we_get_correct_mappings(): void
    {
        $showTourResponseXML = simplexml_load_string(file_get_contents('./tests/TourCMSResponses/TourDepartureStructure/START_TIME.xml'));
        $tourData = $showTourResponseXML->tour;

        $expectedProductOptions = [
            new ProductMapping(
                ProductService::MAPPING_STRUCTURE_TYPE_START_TIME,
                '',
                '',
                ['13:00', '15:00', '17:00']
            ),
        ];

        $productMappings = $this->productMappingFactory->create($tourData);
        $this->assertEquals($expectedProductOptions, $productMappings);
    }

    public function test_when_tour_is_mapped_by_supplier_note_then_we_get_correct_mappings(): void
    {
        $showTourResponseXML = simplexml_load_string(file_get_contents('./tests/TourCMSResponses/TourDepartureStructure/SUPPLIER_NOTE.xml'));
        $tourData = $showTourResponseXML->tour;

        $expectedProductOptions = [
            new ProductMapping(
                ProductService::MAPPING_STRUCTURE_TYPE_SUPPLIER_NOTE,
                '{"en":"","es":""}',
                '',
                ['00:00']
            ),
            new ProductMapping(
                ProductService::MAPPING_STRUCTURE_TYPE_SUPPLIER_NOTE,
                '{"en":""}',
                'TEST_NOTE_1',
                ['00:00']
            ),
            new ProductMapping(
                ProductService::MAPPING_STRUCTURE_TYPE_SUPPLIER_NOTE,
                '{"en":""}',
                'TEST_NOTE_2',
                ['00:00']
            ),
        ];

        $productMappings = $this->productMappingFactory->create($tourData);
        $this->assertEquals($expectedProductOptions, $productMappings);
    }

    public function test_when_tour_is_mapped_by_departure_code_then_we_get_correct_mappings(): void
    {
        $showTourResponseXML = simplexml_load_string(file_get_contents('./tests/TourCMSResponses/TourDepartureStructure/DEPARTURE_CODE.xml'));
        $tourData = $showTourResponseXML->tour;

        $expectedProductOptions = [
            new ProductMapping(
                ProductService::MAPPING_STRUCTURE_TYPE_DEPARTURE_CODE,
                '{"en":"","es":""}',
                'ABC',
                ['00:00']
            ),
            new ProductMapping(
                ProductService::MAPPING_STRUCTURE_TYPE_DEPARTURE_CODE,
                '{"en":""}',
                '123',
                ['00:00']
            ),
            new ProductMapping(
                ProductService::MAPPING_STRUCTURE_TYPE_DEPARTURE_CODE,
                '{"en":""}',
                'xyz',
                ['00:00']
            ),
        ];

        $productMappings = $this->productMappingFactory->create($tourData);
        $this->assertEquals($expectedProductOptions, $productMappings);
    }

    public function test_when_tour_is_mapped_by_supplier_note_plus_start_time_then_we_get_only_non_partial_and_correct_mappings(): void
    {
        $showTourResponseXML = simplexml_load_string(file_get_contents('./tests/TourCMSResponses/TourDepartureStructure/SUPPLIER_NOTE_PLUS_START_TIME.xml'));
        $tourData = $showTourResponseXML->tour;

        $expectedProductOptions = [
            new ProductMapping(
                ProductService::MAPPING_STRUCTURE_TYPE_SUPPLIER_NOTE_PLUS_START_TIME,
                '{"en":"","es":""}',
                'ESP_[*]',
                ['13:00', '17:00']
            ),
            new ProductMapping(
                ProductService::MAPPING_STRUCTURE_TYPE_SUPPLIER_NOTE_PLUS_START_TIME,
                '{"en":"","es":""}',
                '[*]_ESP',
                ['13:00', '17:00']
            ),
        ];

        $productMappings = $this->productMappingFactory->create($tourData);
        $this->assertEquals($expectedProductOptions, $productMappings);
    }

    public function test_when_partial_mapping_has_custom_label_then_product_mapping_carries_it(): void
    {
        $showTourResponseXML = simplexml_load_string(file_get_contents('./tests/TourCMSResponses/TourDepartureStructure/SUPPLIER_NOTE_PLUS_START_TIME_CUSTOM_LABEL.xml'));
        $tourData = $showTourResponseXML->tour;

        $expectedProductOptions = [
            new ProductMapping(
                ProductService::MAPPING_STRUCTURE_TYPE_SUPPLIER_NOTE_PLUS_START_TIME,
                '{"en":"","es":""}',
                'ESP_[*]',
                ['13:00', '17:00'],
                'English - Group'
            ),
            // Second mapping has no <custom_label> element, so it falls back to an empty custom label.
            new ProductMapping(
                ProductService::MAPPING_STRUCTURE_TYPE_SUPPLIER_NOTE_PLUS_START_TIME,
                '{"en":"","es":""}',
                '[*]_ESP',
                ['13:00', '17:00']
            ),
        ];

        $productMappings = $this->productMappingFactory->create($tourData);
        $this->assertEquals($expectedProductOptions, $productMappings);
        $this->assertSame('English - Group', $productMappings[0]->getCustomLabel());
        $this->assertSame('', $productMappings[1]->getCustomLabel());
    }
}
