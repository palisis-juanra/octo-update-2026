<?php

namespace App\Models;

class Place
{
    protected string $latitude;
    protected string $longitude;
    protected PostalAddress $postalAddress;
    protected array $identifiers = [];
    protected array $sameAs = [];

    public function __construct()
    {
        $this->postalAddress = new PostalAddress;
    }

    /**
     * Get the value of latitude
     */ 
    public function getLatitude(): string
    {
        return $this->latitude;
    }

    /**
     * Set the value of latitude
     *
     * @return  self
     */ 
    public function setLatitude(string $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * Get the value of longitude
     */ 
    public function getLongitude(): string
    {
        return $this->longitude;
    }

    /**
     * Set the value of longitude
     *
     * @return  self
     */ 
    public function setLongitude(string $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * Get the value of postalAddress
     */ 
    public function getPostalAddress(): PostalAddress
    {
        return $this->postalAddress;
    }

    /**
     * Set the value of postalAddress
     *
     * @return  self
     */ 
    public function setPostalAddress(PostalAddress $postalAddress): self
    {
        $this->postalAddress = $postalAddress;

        return $this;
    }

    /**
     * Get the value of identifiers
     */ 
    public function getIdentifiers()
    {
        return $this->identifiers;
    }

    /**
     * Set the value of identifiers
     *
     * @return  self
     */ 
    public function setIdentifiers(array $identifiers): self
    {
        $this->identifiers = $identifiers;

        return $this;
    }

    /**
     * Get the value of sameAs
     */ 
    public function getSameAs(): array
    {
        return $this->sameAs;
    }

    /**
     * Set the value of sameAs
     *
     * @return  self
     */ 
    public function setSameAs(array $sameAs): self
    {
        $this->sameAs = $sameAs;

        return $this;
    }
}