<?php

namespace App\Models\TourCMS;

use DateInterval;
use DateTime;
use SimpleXMLElement;

/**
 * Represent a Promotion
 */
class Promotion
{
    public const string OPEN_PROMOTION_NAME = 'OPEN';

    protected string $name;

    protected float $discount;

    protected DateTime $startDate;

    protected DateTime $endDate;

    public function __construct(string $name, float $discount, DateTime $startDate, DateTime $endDate)
    {
        $this->name = $name;
        $this->discount = $discount;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * Creates a Promotion object from XML node coming from
     * https://www.tourcms.com/support/api/mp/get_tour_promotions.php
     */
    public static function fromXML(SimpleXMLElement $promotionXML): self
    {
        $startDate = DateTime::createFromFormat('Y-m-d', (string) $promotionXML->start_date);
        $endDate = DateTime::createFromFormat('Y-m-d', (string) $promotionXML->end_date);

        $startDate->setTime(0, 0, 0, 0);
        $endDate->setTime(23, 59, 59, 0);

        return new Promotion(
            (string) $promotionXML->name,
            (float) $promotionXML->discount,
            $startDate,
            $endDate
        );
    }

    public static function createOpenPromotion()
    {
        return new Promotion(
            self::OPEN_PROMOTION_NAME,
            0,
            (new DateTime)->sub(DateInterval::createFromDateString('10 year')),
            (new DateTime)->add(DateInterval::createFromDateString('10 year'))
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDiscount(): float
    {
        return $this->discount;
    }

    public function getStartDate()
    {
        return $this->startDate;
    }

    public function getEndDate()
    {
        return $this->endDate;
    }
}
