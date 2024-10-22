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
    protected string $address;

    public function __construct(string $id, string $name, string $endpoint, string $website, string $email, string $telephone, string $address)
    {
        $this->id = $id;
        $this->name = $name;
        $this->endpoint = $endpoint;
        $this->website = $website;
        $this->email = $email;
        $this->telephone = $telephone;
        $this->address = $address;
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
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * Set the value of address
     *
     * @return  self
     */
    public function setAddress($address): self
    {
        $this->address = $address;

        return $this;
    }
}
