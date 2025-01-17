<?php

namespace App\Models;

class ProductContent extends BaseModel
{
    protected string $title;
    protected ?string $shortDescription;
    protected ?string $description;
    protected array $features = [];
    protected array $faqs = [];
    protected array $media = [];
    protected array $locations = [];
    protected array $categoryLabels = [];
    protected int $durationMinutesFrom;
    protected ?int $durationMinutesTo = null;
    protected array $commentary = []; 

    /**
     * Get the value of title
     */ 
    public function getTitle(): string
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
     * Get the value of description
     */ 
    public function getDescription(): string|null
    {
        return $this->description;
    }

    /**
     * Set the value of description
     *
     * @return  self
     */ 
    public function setDescription($description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the value of features
     */ 
    public function getFeatures(): array
    {
        return $this->features;
    }

    /**
     * Set the value of features
     *
     * @return  self
     */ 
    public function setFeatures($features): self
    {
        $this->features = $features;

        return $this;
    }

    /**
     * Get the value of faqs
     */ 
    public function getFaqs(): array
    {
        return $this->faqs;
    }

    /**
     * Set the value of faqs
     *
     * @return  self
     */ 
    public function setFaqs($faqs): self
    {
        $this->faqs = $faqs;

        return $this;
    }


    /**
     * Get the value of media
     */ 
    public function getMedia(): array
    {
        return $this->media;
    }

    /**
     * Set the value of media
     *
     * @return  self
     */ 
    public function setMedia($media): self
    {
        $this->media = $media;

        return $this;
    }


    /**
     * Get the value of locations
     */ 
    public function getLocations(): array
    {
        return $this->locations;
    }

    /**
     * Set the value of locations
     *
     * @return  self
     */ 
    public function setLocations($locations): self
    {
        $this->locations = $locations;

        return $this;
    }

    /**
     * Get the value of categoryLabels
     */ 
    public function getCategoryLabels(): array
    {
        return $this->categoryLabels;
    }

    /**
     * Set the value of categoryLabels
     *
     * @return  self
     */ 
    public function setCategoryLabels($categoryLabels): self
    {
        $this->categoryLabels = $categoryLabels;

        return $this;
    }

    /**
     * Get the value of durationMinutesFrom
     */ 
    public function getDurationMinutesFrom(): int
    {
        return $this->durationMinutesFrom;
    }

    /**
     * Set the value of durationMinutesFrom
     *
     * @return  self
     */ 
    public function setDurationMinutesFrom($durationMinutesFrom): self
    {
        $this->durationMinutesFrom = $durationMinutesFrom;

        return $this;
    }

    /**
     * Get the value of durationMinutesTo
     */ 
    public function getDurationMinutesTo(): int|null
    {
        return $this->durationMinutesTo;
    }

    /**
     * Set the value of durationMinutesTo
     *
     * @return  self
     */ 
    public function setDurationMinutesTo($durationMinutesTo): self
    {
        $this->durationMinutesTo = $durationMinutesTo;

        return $this;
    }

    /**
     * Get the value of commentary
     */ 
    public function getCommentary(): array
    {
        return $this->commentary;
    }

    /**
     * Set the value of commentary
     *
     * @return  self
     */ 
    public function setCommentary($commentary): self
    {
        $this->commentary = $commentary;

        return $this;
    }
}