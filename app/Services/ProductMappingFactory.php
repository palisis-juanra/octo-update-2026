<?php

namespace App\Services;

use App\Models\ProductMapping;
use SimpleXMLElement;

class ProductMappingFactory
{
    protected SimpleXMLElement $tour;
    protected string $structureType;
    protected array $mappingAssistantOptions;

    public function __construct() {}

    /**
     * Create product mappings for a given tour XML
     * @param SimpleXMLElement $tour
     * @return ProductMapping[]
     */
    public function create(SimpleXMLElement $tour): array
    {
        $this->tour = $tour;
        $this->structureType = (string) $tour->tour_departure_structure->type;
        $this->mappingAssistantOptions = XMLService::getArrayFromXmlNode($tour->tour_departure_structure->departure_types, 'type');

        switch ($this->structureType) {

            case ProductService::MAPPING_STRUCTURE_TYPE_START_TIME:
                return $this->getStartTimeStructureMappings();

            case ProductService::MAPPING_STRUCTURE_TYPE_DEPARTURE_CODE:
            case ProductService::MAPPING_STRUCTURE_TYPE_SUPPLIER_NOTE:
                return $this->getDepartureCodeOrSupplierNoteStructureMappings();

            case ProductService::MAPPING_STRUCTURE_TYPE_SUPPLIER_NOTE_PLUS_START_TIME:
                return $this->getSupplierNotePlusStartTimeStructureMappings();

            case ProductService::MAPPING_STRUCTURE_TYPE_SINGLE:
            default:
                return $this->getSingleStructureMappings();
        }
    }

    protected function getStartTimeStructureMappings(): array
    {
        $startTimes = [];

        foreach ($this->mappingAssistantOptions as $mapping) {
            if (isset($mapping->active) && $mapping->active == 1 && isset($mapping->fields->field->value)) {
                $startTimes[] = (string) $mapping->fields->field->value;
            }
        }

        return [
            new ProductMapping(
                ProductService::MAPPING_STRUCTURE_TYPE_START_TIME,
                "",
                "",
                $startTimes
            )
        ];
    }

    protected function getDepartureCodeOrSupplierNoteStructureMappings(): array
    {
        $mappings = [];
        foreach ($this->mappingAssistantOptions as $mapping) {
            if (isset($mapping->active) && $mapping->active == 1 && isset($mapping->fields->field->value)) {

                $availabilityStartTime = [ProductService::AVAILABILITY_LOCAL_START_TIMES_DEFAULT];
                if (isset($this->tour->start_time) && !empty($this->tour->start_time) && $this->tour->start_time != ProductService::START_TIME_MULTI) {
                    $availabilityStartTime = [(string) $this->tour->start_time];
                }

                $mappings[] = new ProductMapping(
                    $this->structureType,
                    !empty($mapping->label) ? (string) $mapping->label : '',
                    (string) $mapping->fields->field->value,
                    $availabilityStartTime
                );
            }
        }

        return $mappings;
    }

    protected function getSupplierNotePlusStartTimeStructureMappings(): array
    {
        $mappings = [];
        foreach ($this->mappingAssistantOptions as $mapping) {

            if ($mapping->active == 0) {
                continue;
            }

            $mappingObject = [];

            foreach ($mapping->fields->field as $field) {

                $fieldName = (string) $field->name;
                $fieldValue = (string) $field->value;

                $mappingObject[$fieldName] = $fieldValue;
            }

            if (!array_key_exists($mappingObject['supplier_note'], $mappings)) {
                $mappings[$mappingObject['supplier_note']] = [
                    'times' => [],
                    'label' => !empty($mapping->label) ? (string) $mapping->label : ''
                ];
            }

            if (!in_array($mappingObject['start_time'], $mappings[$mappingObject['supplier_note']])) {
                $mappings[$mappingObject['supplier_note']]['times'][] = $mappingObject['start_time'];
            }
        }

        $productMappings = [];
        foreach ($mappings as $supplierNote => $data) {
            $productMappings[] = new ProductMapping($this->structureType, $data['label'], $supplierNote, $data['times']);
        }
        return $productMappings;
    }

    protected function getSingleStructureMappings(): array
    {
        $startTimes = [];
        if (isset($this->tour->tour_departure_structure->start_times)) {
            $startTimesFromXML = XMLService::getArrayFromXmlNode($this->tour->tour_departure_structure->start_times, 'time');
            foreach ($startTimesFromXML as $startTime) {
                $startTimes[] = (string) $startTime;
            }
        }

        if (empty($startTimes)) {
            $startTimes = ['09:00'];
        }

        $singleMapping = new ProductMapping(ProductService::MAPPING_STRUCTURE_TYPE_SINGLE, "", '', $startTimes);
        return [$singleMapping];
    }


}
