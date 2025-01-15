<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected string $id;
    protected string $name;
    protected string $endpoint;
    protected string $website;
    protected string $email;
    protected string $telephone;
    protected string $address_1;
    protected string $address_2;
    protected string $address_city;
    protected string $address_state;
    protected string $address_postcode;
    protected string $address_country;
    protected string $shortDescription;
    protected array $media;

    public function __construct(string $id, string $name, string $endpoint, string $website, string $email, string $telephone, string $address_1, string $address_2, string $address_city, string $address_state, string $address_postcode, string $address_country, string|null $shortDescription, array $media)
    {
        $this->id = $id;
        $this->name = $name;
        $this->endpoint = $endpoint;
        $this->website = $website;
        $this->email = $email;
        $this->telephone = $telephone;
        $this->address_1 = $address_1;
        $this->address_2 = $address_2;
        $this->address_city = $address_city;
        $this->address_state = $address_state;
        $this->address_postcode = $address_postcode;
        $this->address_country = $address_country;
        $this->shortDescription = $shortDescription;
        $this->media = $media;
    }

    /**
     * Get the value of id
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Set the value of id
     *
     * @return  self
     */
    public function setId($id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the value of name
     *
     * @return  self
     */
    public function setName($name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the value of endpoint
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * Set the value of endpoint
     *
     * @return  self
     */
    public function setEndpoint($endpoint): self
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    /**
     * Get the value of website
     */
    public function getWebsite(): string
    {
        return $this->website;
    }

    /**
     * Set the value of website
     *
     * @return  self
     */
    public function setWebsite($website): self
    {
        $this->website = $website;

        return $this;
    }

    /**
     * Get the value of email
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Set the value of email
     *
     * @return  self
     */
    public function setEmail($email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get the value of telephone
     */
    public function getTelephone(): string
    {
        return $this->telephone;
    }

    /**
     * Set the value of telephone
     *
     * @return  self
     */
    public function setTelephone($telephone): self
    {
        $this->telephone = $telephone;

        return $this;
    }

    /**
     * Get the value of address
     */
    public function getFullAddress(): string
    {
        $address = $this->address_1;
        $address .= empty($this->address_2)? '' : ' '.$this->address_2;
        $address .= empty($this->address_postcode)? '' : ', '.$this->address_postcode;
        $address .= empty($this->address_city)? '' : ', '.$this->address_city; 
        $address .= empty($this->address_country)? '' : ' ('.$this->address_country.')';
        return $address;
    }

    /**
     * Set the value of address
     *
     * @return  self
     */
    public function setAddress($address): self
    {
        $this->address_1 = $address;
        return $this;
    }
    public function setShortDescription($shortDescription): self
    {
        $this->shortDescription = $shortDescription;
        return $this;
    }
    public function setMedia($media): self
    {
        $this->media = $media;
        return $this;
    }
    protected function getAddress1(): string 
    {
        return $this->address_1;
    }
    protected function getAddress2(): string 
    {
        return $this->address_2;
    }
    protected function getPostCode(): string 
    {
        return $this->address_postcode;
    }
    protected function getCity(): string 
    {
        return $this->address_city;
    }
    protected function getCountry(): string 
    {
        return $this->address_country ;
    }
    public function getShortDescription(): string 
    {
        return $this->shortDescription;
    }
    public function getMedia(): array 
    {
        return $this->media;
    }
}
