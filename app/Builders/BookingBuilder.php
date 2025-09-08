<?php
namespace App\Builders;

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
}