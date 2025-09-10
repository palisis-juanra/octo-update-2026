<?php
namespace App\Builders;

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
        'unitItems[]->ticket->redemptionMethod',
        'unitItems[]->ticket->deliveryOptions->deliveryFormat',
        'unitItems[]->ticket->deliveryOptions->deliveryValue',
        'unitItems[]->contact->fullName',
        'unitItems[]->contact->firstName',
        'unitItems[]->contact->lastName',
        'unitItems[]->contact->emailAddress',
        'unitItems[]->contact->phoneNumber',
        'unitItems[]->contact->locales',
        'unitItems[]->contact->postalCode',
        'unitItems[]->contact->country',
        'unitItems[]->contact->notes'

    ];

    public function updateStoredJsonWithNewInformation(stdClass $storedBooking, stdClass $updatedBooking): stdClass {
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

    protected function setNestedProp(&$obj, $path, $value) 
    {
        if (is_array($value)) {
            foreach ($value as $index => $val) {
                $this->setNestedProp($obj, str_replace("[]", "[$index]", $path), $val);
            }
        } else {
            $parts = explode("->", $path);
            $current = &$obj;
            
            foreach ($parts as $i => $part) {
                // If current is null and not the last part, stop processing
                if ($i < count($parts) - 1 && $current === null) {
                    return;
                }
                
                if (preg_match('/^(.+)\[(\d+)\]$/', $part, $matches)) {
                    $arrayName = $matches[1];
                    $index = $matches[2];
                    
                    // Check if the array and index exist and are not null
                    if (!isset($current->$arrayName) || 
                        !isset($current->$arrayName[$index]) || 
                        $current->$arrayName[$index] === null) {
                        return;
                    }
                    
                    $current = &$current->$arrayName[$index];
                } elseif ($part === '[]') {
                    // Handle anonymous array (e.g., unitItems[])
                    if (!is_array($current)) {
                        return;
                    }
                    $current = &$current[];
                } else {
                    if ($i === count($parts) - 1) {
                        // Assign value if it's the last part and current is not null
                        if ($current === null) {
                            return; 
                        }
                        
                        if (is_object($current)) {
                            $current->$part = $value;
                        } elseif (is_array($current)) {
                            $current[$part] = $value;
                        }
                    } else {
                        // Navigate deeper if not the last part
                        if (is_object($current)) {
                            if (isset($current->$part) && $current->$part === null) {
                                return;
                            }
                            $current = &$current->$part;
                        } elseif (is_array($current)) {
                            if (isset($current[$part]) && $current[$part] === null) {
                                return;
                            }
                            $current = &$current[$part];
                        }
                    }
                }
            }
        }
    }
}