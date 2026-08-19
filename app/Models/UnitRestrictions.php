<?php

namespace App\Models;

class UnitRestrictions
{
    const MIN_AGE_DEFAULT = 1;

    const MAX_AGE_DEFAULT = 99;

    const PAX_COUNT_DEFAULT = 1;

    protected int $minAge = self::MIN_AGE_DEFAULT;

    protected int $maxAge = self::MAX_AGE_DEFAULT;

    protected bool $idRequired = false;

    protected ?int $minQuantity = null;

    protected ?int $maxQuantity = null;

    protected int $paxCount = self::PAX_COUNT_DEFAULT;

    protected array $accompaniedBy = [];

    public function __construct() {}

    /**
     * Get the value of minAge
     */
    public function getMinAge()
    {
        return $this->minAge;
    }

    /**
     * Set the value of minAge
     *
     * @return self
     */
    public function setMinAge($minAge)
    {
        $this->minAge = $minAge;

        return $this;
    }

    /**
     * Get the value of maxAge
     */
    public function getMaxAge()
    {
        return $this->maxAge;
    }

    /**
     * Set the value of maxAge
     *
     * @return self
     */
    public function setMaxAge($maxAge)
    {
        $this->maxAge = $maxAge;

        return $this;
    }

    /**
     * Get the value of idRequired
     */
    public function getIdRequired()
    {
        return $this->idRequired;
    }

    /**
     * Set the value of idRequired
     *
     * @return self
     */
    public function setIdRequired($idRequired)
    {
        $this->idRequired = $idRequired;

        return $this;
    }

    /**
     * Get the value of minQuantity
     */
    public function getMinQuantity()
    {
        return $this->minQuantity;
    }

    /**
     * Set the value of minQuantity
     *
     * @return self
     */
    public function setMinQuantity($minQuantity)
    {
        $this->minQuantity = $minQuantity;

        return $this;
    }

    /**
     * Get the value of maxQuantity
     */
    public function getMaxQuantity()
    {
        return $this->maxQuantity;
    }

    /**
     * Set the value of maxQuantity
     *
     * @return self
     */
    public function setMaxQuantity($maxQuantity)
    {
        $this->maxQuantity = $maxQuantity;

        return $this;
    }

    /**
     * Get the value of paxCount
     */
    public function getPaxCount()
    {
        return $this->paxCount;
    }

    /**
     * Set the value of paxCount
     *
     * @return self
     */
    public function setPaxCount($paxCount)
    {
        $this->paxCount = $paxCount;

        return $this;
    }

    /**
     * Get the value of accompaniedBy
     */
    public function getAccompaniedBy()
    {
        return $this->accompaniedBy;
    }

    /**
     * Set the value of accompaniedBy
     *
     * @return self
     */
    public function setAccompaniedBy($accompaniedBy)
    {
        $this->accompaniedBy = $accompaniedBy;

        return $this;
    }
}
