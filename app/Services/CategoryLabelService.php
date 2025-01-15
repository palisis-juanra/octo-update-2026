<?php

namespace App\Services;

use SimpleXMLElement;

class CategoryLabelService
{
    public const TCMS_TAG_FIELD = 'tag';
    public const LABEL_SELF_GUIDED = 'self-guided';
    public const LABEL_GUIDED_TOURS = 'guided-tours';
    public const LABEL_MULTI_DAY = 'multi-day';
    public const LABEL_ACCOMMODATION_INCLUDED = 'accommodation-included';
    public const LABEL_TRIP_DIFFICULTY_EASY = 'trip-difficulty-easy';
    public const LABEL_TRIP_DIFFICULTY_MEDIUM = 'trip-difficulty-medium';
    public const LABEL_TRIP_DIFFICULTY_HARD = 'trip-difficulty-hard';
    public const TCMS_SELF_GUIDED = 2;
    public const TCMS_GUIDED_TOURS = 1;
    public const TCMS_MULTI_DAY = 3;
    public const TCMS_ACCOMMODATION_INCLUDED = 1;

    public const TCMS_TOUR_TAGS_TO_CATEGORY_LABELS = [
        'adults-only' => 'adults-only',
        'animals' => 'animals',
        'audio-guide' => 'audio-guide',
        'beaches' => 'beaches',
        'bike-tours' => 'bike-tours',
        'boat-tours' => 'boat-tours',
        'city-cards' => 'city-cards',
        'classes' => 'classes',
        'day-trips' => 'day-trips',
        'family-friendly' => 'family-friendly',
        'fast-track' => 'fast-track',
        'food' => 'food',
        'history' => 'history',
        'hop-on-hop-off' => 'hop-on-hop-off',
        'literature' => 'literature',
        'live-music' => 'live-music',
        'museums' => 'museums',
        'nightlife' => 'nightlife',
        'outdoors' => 'outdoors',
        'private-tours '=> 'private-tours',
        'romantic' => 'romantic',
        'small-group-tours' => 'small-group-tours',
        'sports' => 'sports',
        'theme-parks' => 'theme-parks',
        'walking-tours' => 'walking-tours',
        'suitable-for-wheelchairs' => 'wheelchair-accessible'
    ];

    public static function getFromTourXML(SimpleXMLElement $tour): array
    {
        $categoryLabels = [];
        $tourTags = XMLService::getArrayFromXmlNode($tour->tour_tags, self::TCMS_TAG_FIELD);
        foreach ($tourTags as $tag) {
            if (!empty($tag->token) && array_key_exists((string) $tag->token, self::TCMS_TOUR_TAGS_TO_CATEGORY_LABELS)) {
                $categoryLabels[] = self::TCMS_TOUR_TAGS_TO_CATEGORY_LABELS[(string) $tag->token];
            }
        }

        if (((int) $tour->tourleader_type) == self::TCMS_SELF_GUIDED) {
            $categoryLabels[] = self::LABEL_SELF_GUIDED;
        } else if ((int) $tour->tourleader_type == self::TCMS_GUIDED_TOURS) {
            $categoryLabels[] = self::LABEL_GUIDED_TOURS;
        }

        if (((int) $tour->product_type) == self::TCMS_MULTI_DAY) {
            $categoryLabels[] = self::LABEL_MULTI_DAY;
        }

        if (((int) $tour->accomrating) > self::TCMS_ACCOMMODATION_INCLUDED) {
            $categoryLabels[] = self::LABEL_ACCOMMODATION_INCLUDED;
        }

        switch ((int) $tour->grade) {
            case 1:
                $categoryLabels[] = self::LABEL_TRIP_DIFFICULTY_EASY;
                break;
            case 2:
            case 3:
                $categoryLabels[] = self::LABEL_TRIP_DIFFICULTY_MEDIUM;
                break;
            case 4:
            case 5:
                $categoryLabels[] = self::LABEL_TRIP_DIFFICULTY_HARD;
                break;
        }

        return $categoryLabels;
    }
}