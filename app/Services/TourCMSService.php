<?php

namespace App\Services;

use App\Exceptions\APICallNotOKException;
use App\Exceptions\FailSignatureException;
use App\Http\Middleware\OctoAuthentication;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\Request;
use TourCMS\Utils\TourCMS;

class TourCMSService
{
    const ERROR_FAIL_SIG = 'FAIL_SIG';
    const ERROR_OK = 'OK';
    const RESPONSE_FORMAT_SIMPLEXML = 'simplexml';
    private TourCMS $tourCMS;

    public function __construct(Request $request)
    {
        $maid = $request->get(OctoAuthentication::FIELD_MAID);
        $APIKey = $request->get(OctoAuthentication::FIELD_API_KEY);

        $this->tourCMS = new TourCMS($maid, $APIKey, self::RESPONSE_FORMAT_SIMPLEXML);
        $this->tourCMS->set_base_url($this->getAPIBaseUrl());
    }

    public function showChannel(string $channelId): SimpleXMLElement
    {
        $response = $this->tourCMS->show_channel($channelId);
        $response = $this->handleResponse($response);

        return $response;
    }

    protected function getAPIBaseUrl()
    {
        return env('API_BASE_URL', 'https://api.tourcms.com');
    }

    /**
     * Handle response
     * @throws FailSignatureException
     * @throws APICallNotOKException
     * @return SimpleXMLElement
     */
    protected function handleResponse(mixed $response): SimpleXMLElement
    {
        if ((string) $response->error == self::ERROR_FAIL_SIG) {
            throw new FailSignatureException();
        }
        
        if ((string) $response->error !== self::ERROR_OK) {
            throw new APICallNotOKException();
        }

        // Although this is checked of wrapper, we need to check here to 
        // assure that return type is SimpleXMLElement object
        if (!($response instanceof SimpleXMLElement)) {
            $response = simplexml_load_string($response);
        }

        return $response;

    }
}
