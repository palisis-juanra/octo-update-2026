<?php

namespace App\Models\TourCMS;

use DateTime;
use SimpleXMLElement;

/**
 * Represent a Promotion
 */
class Promotion
{
    protected string $name;
    protected float $discount;
    protected DateTime $startDate;
    protected DateTime $endDate;

    protected function __construct(string $name, float $discount, DateTime $startDate, DateTime $endDate)
    {
        $this->name = $name;
        $this->discount = $discount;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }


    /**
     * Creates a Promotion object from XML node coming from
     * https://www.tourcms.com/support/api/mp/get_tour_promotions.php
     * 
     * @param SimpleXMLElement $promotionXML
     * @return Promotion
     */
    public static function fromXML(SimpleXMLElement $promotionXML): self
    {
        $startDate = DateTime::createFromFormat('Y-m-d', (string) $promotionXML->start_date);
        $endDate = DateTime::createFromFormat('Y-m-d', (string) $promotionXML->end_date);

        $startDate->setTime(0,0,0,0);
        $endDate->setTime(23, 59, 59, 0);

        return new Promotion(
            (string) $promotionXML->name,
            (float) $promotionXML->discount,
            $startDate,
            $endDate
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
}