<?php

namespace App\Services;

use App\Exceptions\APICallNotOKException;
use App\Exceptions\FailSignatureException;
use App\Exceptions\NoMatchingDataException;
use SimpleXMLElement;
use TourCMS\Utils\TourCMS;

class TourCMSService
{
    const ERROR_FAIL_SIG = 'FAIL_SIG';
    const NO_MATCHING_DATA = 'NO MATCHING DATA';
    const ERROR_OK = 'OK';
    const DEFAULT_API_BASE_URL = 'https://api.tourcms.com';
    const RESPONSE_FORMAT_SIMPLEXML = 'simplexml';
    const LIST_TOURS_EXTENDED_TOUR_INFO_PARAM = 'extended_tour_info=1';
    private TourCMS $tourCMS;

    public function __construct(string $maid, string $APIKey)
    {
        $this->tourCMS = new TourCMS($maid, $APIKey, self::RESPONSE_FORMAT_SIMPLEXML);
        $this->tourCMS->set_base_url($this->getAPIBaseUrl());
    }

    public function showChannel(string $channelId): SimpleXMLElement
    {
        $response = $this->tourCMS->show_channel($channelId);
        $response = $this->handleResponse($response);

        return $response;
    }

    public function listTours(string $channelId, string $params = ""): SimpleXMLElement
    {
        $response = $this->tourCMS->list_tours($channelId, $params);
        $response = $this->handleResponse($response);
        return $response;
    }

    public function showTour(string $tourId, string $channelId): SimpleXMLElement
    {
        $response = $this->tourCMS->show_tour($tourId, $channelId);
        $response = $this->handleResponse($response);
        return $response;
    }

    public function getArrayFromXmlNode($parent, $childName = ''):array
    {
        $children = [];
        foreach ($parent->children() as $child) {
            if (empty($childName) || $child->getName() == $childName) {
                $children[] = $child;
            }
        }
        return $children;
    }

    protected function getAPIBaseUrl()
    {
        return env('API_BASE_URL', self::DEFAULT_API_BASE_URL);
    }

    /**
     * Handle response
     * @throws FailSignatureException
     * @throws APICallNotOKException
     * @return SimpleXMLElement
     */
    protected function handleResponse(mixed $response): SimpleXMLElement
    {
        switch ((string)$response->error) {
            case self::ERROR_OK:
                if (!($response instanceof SimpleXMLElement)) {
                    $response = simplexml_load_string($response);
                }
                return $response;
            case self::ERROR_FAIL_SIG:
                throw new FailSignatureException();
            case self::NO_MATCHING_DATA:
                throw new NoMatchingDataException();
            default:
                throw new APICallNotOKException();
        }
    }
}
