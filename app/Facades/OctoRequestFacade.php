<?php

namespace App\Facades;

use App\Http\Requests\OctoRequest;
use Illuminate\Support\Facades\Facade;

class OctoRequestFacade extends Facade
{
    /**
    * @method static bool isCapabilityActive($capability)
    */

    protected static function getFacadeAccessor(): string
    {
        return OctoRequest::class;
    }
}