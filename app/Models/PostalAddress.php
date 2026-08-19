<?php

namespace App\Models;

class PostalAddress
{
    protected ?string $streetAddress = null;

    protected ?string $addressLocality = null;

    protected ?string $addressRegion = null;

    protected ?string $postalCode = null;

    protected ?string $addressCountry = null;

    protected ?string $postOfficeBoxNumber = null;

    /**
     * Get the value of streetAddress
     */
    public function getStreetAddress(): ?string
    {
        return $this->streetAddress;
    }

    /**
     * Set the value of streetAddress
     */
    public function setStreetAddress(?string $streetAddress): self
    {
        $this->streetAddress = $streetAddress;

        return $this;
    }

    /**
     * Get the value of addressLocality
     */
    public function getAddressLocality(): ?string
    {
        return $this->addressLocality;
    }

    /**
     * Set the value of addressLocality
     */
    public function setAddressLocality(?string $addressLocality): self
    {
        $this->addressLocality = $addressLocality;

        return $this;
    }

    /**
     * Get the value of addressRegion
     */
    public function getAddressRegion(): ?string
    {
        return $this->addressRegion;
    }

    /**
     * Set the value of addressRegion
     */
    public function setAddressRegion(?string $addressRegion): self
    {
        $this->addressRegion = $addressRegion;

        return $this;
    }

    /**
     * Get the value of postalCode
     */
    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    /**
     * Set the value of postalCode
     */
    public function setPostalCode(?string $postalCode): self
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    /**
     * Get the value of addressCountry
     */
    public function getAddressCountry(): ?string
    {
        return $this->addressCountry;
    }

    /**
     * Set the value of addressCountry
     */
    public function setAddressCountry(?string $addressCountry): self
    {
        $this->addressCountry = $addressCountry;

        return $this;
    }

    /**
     * Get the value of postOfficeBoxNumber
     */
    public function getPostOfficeBoxNumber(): ?string
    {
        return $this->postOfficeBoxNumber;
    }

    /**
     * Set the value of postOfficeBoxNumber
     */
    public function setPostOfficeBoxNumber(?string $postOfficeBoxNumber): self
    {
        $this->postOfficeBoxNumber = $postOfficeBoxNumber;

        return $this;
    }
}
