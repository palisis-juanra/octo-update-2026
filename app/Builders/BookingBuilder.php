<?php
namespace App\Builders;

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

    public function buildUnitItemFromJSON(array $data): Unit
    {
        $unit = new Unit();
        $unit->setId($data['unit']['id']);
        $unit->setType($data['unit']['type'] ?? null);
        $unit->setInternalName(UnitService::getTourCMSRateId($data['unit']['id']));
        $unit->setRateId(UnitService::getTourCMSRateId($data['unit']['id']));
        $unit->setReference($data['unit']['reference'] ?? null);
        $unit->setRequiredContactFields($data['unit']['requiredContactFields'] ?? []);
        $unit->setRestrictions($this->buildUnitRestriction($data['unit']['restrictions'] ?? []));
        return $unit;
    }

    
    public function buildUnitRestriction(array $data)
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