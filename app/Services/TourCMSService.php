<?php

namespace App\Services;

use App\Http\Middleware\OctoAuthentication;
use Symfony\Component\HttpFoundation\Request;
use TourCMS\Utils\TourCMS;

class TourCMSService
{
    const RESPONSE_FORMAT_SIMPLEXML = 'simplexml';
    public TourCMS $tourCMS;
    public function __construct(Request $request)
    {
        $maid = $request->get(OctoAuthentication::FIELD_MAID);
        $APIKey = $request->get(OctoAuthentication::FIELD_API_KEY);

        $this->tourCMS = new TourCMS($maid, $APIKey, self::RESPONSE_FORMAT_SIMPLEXML);
        $this->tourCMS->set_base_url(env('API_BASE_URL'));
    }
}