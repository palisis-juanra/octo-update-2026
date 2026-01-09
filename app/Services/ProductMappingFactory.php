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
                if (isset($this->tour->start_time) && DateTimeService::validateTime((string) $this->tour->start_time) && $this->tour->start_time != ProductService::START_TIME_MULTI) {
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
        $productMappings = [];
        foreach ($this->mappingAssistantOptions as $mapping) {

            if ($mapping->partial == 0 || $mapping->active == 0) {
                continue;
            }

            $supplierNote = (string) $mapping->fields->field->value;

            if ($mapping->partial == 1) {
                $supplierNote .= "[*]";
            } else {
                $supplierNote = "[*]{$supplierNote}";
            }


            $label = !empty($mapping->label) ? (string) $mapping->label : '';

            $startTimes = [];
            $startTimesXML = XMLService::getArrayFromXmlNode($mapping->start_times, 'start_time');
            foreach ($startTimesXML as $startTime) {
                $startTimes[] = (string) $startTime;
            }

            $productMappings[] = new ProductMapping($this->structureType, $label, $supplierNote, $startTimes);
            
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
