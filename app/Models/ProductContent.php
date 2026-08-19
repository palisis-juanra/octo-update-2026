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
     */
    public function setTitle($title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the value of shortDescription
     */
    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    /**
     * Set the value of shortDescription
     */
    public function setShortDescription($shortDescription): self
    {
        $this->shortDescription = $shortDescription;

        return $this;
    }

    /**
     * Get the value of description
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set the value of description
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
     */
    public function setDurationMinutesFrom($durationMinutesFrom): self
    {
        $this->durationMinutesFrom = $durationMinutesFrom;

        return $this;
    }

    /**
     * Get the value of durationMinutesTo
     */
    public function getDurationMinutesTo(): ?int
    {
        return $this->durationMinutesTo;
    }

    /**
     * Set the value of durationMinutesTo
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
     */
    public function setCommentary($commentary): self
    {
        $this->commentary = $commentary;

        return $this;
    }
}
