<?php

namespace App\Models;

class Contact extends BaseModel
{
    const FIELD_FULL_NAME = 'fullName';
    const FIELD_FIRST_NAME = 'firstName';
    const FIELD_LAST_NAME = 'lastName';
    const FIELD_EMAIL_ADDRESS = 'emailAddress';
    const FIELD_PHONE_NUMBER = 'phoneNumber';
    const FIELD_LOCALES = 'locales';
    const FIELD_POSTAL_CODE = 'postalCode';
    const FIELD_COUNTRY = 'country';
    const FIELD_NOTES = 'notes';

    protected ?string $fullName = null;
    protected ?string $firstName = null;
    protected ?string $lastName = null;
    protected ?string $emailAddress = null;
    protected ?string $phoneNumber = null;
    protected ?array $locales = [];
    protected ?string $postalCode = null;
    protected ?string $country = null;
    protected ?string $notes = null;

    public function __construct()
    {

    }

    public static function create(array $attributes): Contact
    {
        $contact = new Contact();

        $contact->fullName = $attributes[self::FIELD_FULL_NAME] ?? null;
        $contact->firstName = $attributes[self::FIELD_FIRST_NAME] ?? null;
        $contact->lastName = $attributes[self::FIELD_LAST_NAME] ?? null;
        $contact->emailAddress = $attributes[self::FIELD_EMAIL_ADDRESS]?? null;
        $contact->phoneNumber = $attributes[self::FIELD_PHONE_NUMBER] ?? null;
        $contact->locales = $attributes[self::FIELD_LOCALES] ?? [];
        $contact->postalCode = $attributes[self::FIELD_POSTAL_CODE] ?? null;
        $contact->country = $attributes[self::FIELD_COUNTRY] ?? null;
        $contact->notes = $attributes[self::FIELD_NOTES] ?? null;

        return $contact;
    }

    /**
     * Get the value of fullName
     */ 
    public function getFullName()
    {
        return $this->fullName;
    }

    /**
     * Set the value of fullName
     *
     * @return  self
     */ 
    public function setFullName($fullName)
    {
        $this->fullName = $fullName;

        return $this;
    }

    /**
     * Get the value of firstName
     */ 
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set the value of firstName
     *
     * @return  self
     */ 
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get the value of lastName
     */ 
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set the value of lastName
     *
     * @return  self
     */ 
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get the value of emailAddress
     */ 
    public function getEmailAddress()
    {
        return $this->emailAddress;
    }

    /**
     * Set the value of emailAddress
     *
     * @return  self
     */ 
    public function setEmailAddress($emailAddress)
    {
        $this->emailAddress = $emailAddress;

        return $this;
    }

    /**
     * Get the value of phoneNumber
     */ 
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    /**
     * Set the value of phoneNumber
     *
     * @return  self
     */ 
    public function setPhoneNumber($phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    /**
     * Get the value of locales
     */ 
    public function getLocales()
    {
        return $this->locales;
    }

    /**
     * Set the value of locales
     *
     * @return  self
     */ 
    public function setLocales($locales)
    {
        $this->locales = $locales;

        return $this;
    }

    /**
     * Get the value of postalCode
     */ 
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * Set the value of postalCode
     *
     * @return  self
     */ 
    public function setPostalCode($postalCode)
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    /**
     * Get the value of country
     */ 
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set the value of country
     *
     * @return  self
     */ 
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get the value of notes
     */ 
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Set the value of notes
     *
     * @return  self
     */ 
    public function setNotes($notes)
    {
        $this->notes = $notes;

        return $this;
    }

}