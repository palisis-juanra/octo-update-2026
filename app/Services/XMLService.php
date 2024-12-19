<?php

namespace App\Services;

use SimpleXMLElement;

class XMLService
{
    public static function getArrayFromXmlNode(SimpleXMLElement $parent, string $childName = ''): array
    {
        $children = [];
        foreach ($parent->children() as $child) {
            if (empty($childName) || $child->getName() == $childName) {
                $children[] = $child;
            }
        }
        return $children;
    }
}