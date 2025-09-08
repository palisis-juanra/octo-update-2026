<?php
namespace App\Builders;

use App\Models\Booking;
use ReflectionClass;
use stdClass;

class BookingChecker
{
    const UPDATABLE_PROPERTIES = [
        'status',
        'utcUpdatedAt',
        'utcExpiresAt',
        'utcRedeemedAt',
        'utcConfirmedAt',
    ];

    const NESTED_UPDATABLE_PROPERTIES = [
        'unitItems[]->utcRedeemedAt',
        'unitItems[]->ticket->utcRedeemedAt',
        'unitItems[]->contact',
    ];

    public function updateStoredJsonWithNewInformation(stdClass $storedBooking, stdClass $updatedBooking): stdClass {
        $reflection = new ReflectionClass($this);
        $status = $updatedBooking->status;
        foreach (self::UPDATABLE_PROPERTIES as $property) {
            if ($updatedBooking->$property !== null) {
                $storedBooking->$property = $updatedBooking->$property;
            }
        }

        foreach (self::NESTED_UPDATABLE_PROPERTIES as $nestedProperty) {
            $newValue = $this->getNestedProp($updatedBooking, $nestedProperty);
            if (!is_null($newValue)) {
                $this->setNestedProp($storedBooking, $nestedProperty, $newValue);
            }
        }

        foreach ($storedBooking->unitItems as &$unitItem) {
            $unitItem['status'] = $status;
        }

        return $storedBooking;
    }

    protected function getNestedProp($obj, string $path)
    {
        $parts = explode("->", $path);

        foreach ($parts as $part) {
            if (str_ends_with($part, "[]")) {
                $prop = substr($part, 0, -2);

                if (is_object($obj) && isset($obj->$prop)) {
                    $obj = (array) $obj->$prop;
                } elseif (is_array($obj) && isset($obj[$prop])) {
                    $obj = (array) $obj[$prop];
                } else {
                    return null;
                }

                $remainingPath = implode("->", array_slice($parts, array_search($part, $parts) + 1));
                if ($remainingPath === "") {
                    return $obj;
                }

                $results = [];
                foreach ($obj as $item) {
                    $results[] = $this->getNestedProp($item, $remainingPath);
                }
                return $results;
            }

            if (is_object($obj) && isset($obj->$part)) {
                $obj = $obj->$part;
            }
            
            elseif (is_array($obj) && isset($obj[$part])) {
                $obj = $obj[$part];
            } else {
                return null;
            }
        }

        return $obj;
    }

    protected function setNestedProp(&$obj, $path, $value) {
        $parts = explode("->", $path);
        $current = &$obj;
        foreach ($parts as $part) {
            if (!isset($current->$part)) {
                $current->$part = new stdClass();
            }
            $current = &$current->$part;
        }
        $current = $value;
    }
}