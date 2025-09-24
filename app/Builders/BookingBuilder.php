<?php
namespace App\Builders;

use App\Models\Option;
use App\Models\OptionContent;
use App\Models\Unit;
use App\Services\UnitService;
use ReflectionClass;
use stdClass;

class BookingBuilder
{
    public string $id;
    public string $uuid;
    public bool $testMode;
    public string $resellerReference;
    public string $supplierReference;
    public string $status;
    public string $utcCreatedAt;
    public string $utcUpdatedAt;
    public string $utcExpiresAt;
    public string $utcRedeemedAt;
    public string $utcConfirmedAt;
    public string $productId;
    public object $product;
    public string $optionId;
    public object $option;
    public bool $cancellable;
    public object $cancellation;
    public bool $freesale;
    public string $availabilityId;
    public object $availability;
    public object $contact;
    public object $deliveryMethods;
    public object $unitItems;
    public object $voucher;
    public string $notes;

    public final function build(array $data, string $node = ''): stdClass
    {
        $object = new stdClass();
        $reflection = new ReflectionClass($this);

        foreach ($data as $key => $value) {
            if(empty($node)) {
                $node = $key;
            }
            $prop = $reflection->getProperty($node);
            $type = $prop->getType();
            if ($type->getName() === 'object') {
                $object->$node = $this->build((array) $value, $node);
            } else {
                $object->$key = $value;
            }
        }
        return $object;
    }

    public static function buildUnitItemFromJSON(array $data): Unit
    {
        $unit = new Unit();
        $unit->setId($data['id']);
        $unit->setType($data['type'] ?? null);
        $unit->setInternalName(UnitService::getTourCMSRateId($data['id']));
        $unit->setRateId(UnitService::getTourCMSRateId($data['id']));
        $unit->setReference($data['reference'] ?? null);
        $unit->setRequiredContactFields($data['requiredContactFields'] ?? []);
        $unit->setRestrictions(self::buildUnitRestriction($data['restrictions'] ?? []));
        return $unit;
    }

    public static function buildOptionsFromJSON(array $optionsArray): array
    {
        $options = [];
        foreach ($optionsArray as $option) {
            $optionData = new stdClass();
            $optionData->id = $option['id'];
            $optionData->default = $option['default'];
            $optionData->internalName = $option['internalName'];
            $optionData->reference = $option['reference'];
            $optionData->availabilityLocalStartTimes = $option['availabilityLocalStartTimes'];
            $optionData->cancellationCutoff = $option['cancellationCutoff'];
            $optionData->cancellationCutoffAmount = $option['cancellationCutoffAmount'];
            $optionData->cancellationCutoffUnit = $option['cancellationCutoffUnit'];
            $optionData->requiredContactFields = $option['requiredContactFields'];
            $optionData->restrictions = (object)$option['restrictions'];
            $units = [];
            foreach ($option['units'] as $unitArray) {
                $unitObject = self::buildUnitItemFromJSON($unitArray);
                $units[] = $unitObject;
            }
            $optionData->units = $units;

            $optionObject = Option::create($optionData);
            if (isset($option['title'])){
                $optionContent = new OptionContent($option['title']);
                $optionObject->setContent($optionContent);
            }
            $options[] = $optionObject;
        }
        return $options;
    }

    public static function buildUnitRestriction(array $data)
    {
        $unitRestriction = new \App\Models\UnitRestrictions();
        foreach ($data as $key => $value) {
            $method = 'set' . $key;
            if (method_exists($unitRestriction, $method)) {
                $unitRestriction->$method($value);
            }
        }
        return $unitRestriction;
    }
}