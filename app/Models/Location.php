<?php

namespace App\Models;

class Location
{
    public const TYPE_START = 'START';
    public const TYPE_ITINERARY_ITEM = 'ITINERARY_ITEM';
    public const TYPE_POINT_OF_INTEREST = 'POINT_OF_INTEREST';
    public const TYPE_ADMISSION_INCLUDED = 'ADMISSION_INCLUDED';
    public const TYPE_END = 'END';

    protected ?string $title = null;
    protected ?string $shortDescription = null;
    protected array $types;
    protected ?int $minutesTo = null;
    protected ?int $minutesAt = null;
    protected Place $place;

    /**
     * Get the value of title
     */ 
    public function getTitle(): string|null
    {
        return $this->title;
    }

    /**
     * Set the value of title
     *
     * @return  self
     */ 
    public function setTitle($title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the value of shortDescription
     */ 
    public function getShortDescription(): string|null
    {
        return $this->shortDescription;
    }

    /**
     * Set the value of shortDescription
     *
     * @return  self
     */ 
    public function setShortDescription($shortDescription): self
    {
        $this->shortDescription = $shortDescription;

        return $this;
    }

    /**
     * Get the value of types
     */ 
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * Set the value of types
     *
     * @return  self
     */ 
    public function setTypes($types): self
    {
        $this->types = $types;

        return $this;
    }

    /**
     * Get the value of minutesTo
     */ 
    public function getMinutesTo(): int|null
    {
        return $this->minutesTo;
    }

    /**
     * Set the value of minutesTo
     *
     * @return  self
     */ 
    public function setMinutesTo($minutesTo): self
    {
        $this->minutesTo = $minutesTo;

        return $this;
    }

    /**
     * Get the value of minutesAt
     */ 
    public function getMinutesAt(): int|null
    {
        return $this->minutesAt;
    }

    /**
     * Set the value of minutesAt
     *
     * @return  self
     */ 
    public function setMinutesAt($minutesAt): self
    {
        $this->minutesAt = $minutesAt;

        return $this;
    }

    /**
     * Get the value of place
     */ 
    public function getPlace(): Place
    {
        return $this->place;
    }

    /**
     * Set the value of place
     *
     * @return  self
     */ 
    public function setPlace(Place $place): self
    {
        $this->place = $place;

        return $this;
    }
}