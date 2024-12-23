<?php

namespace App\Services;

use App\Exceptions\APICallNotOKException;
use App\Exceptions\FailSignatureException;
use App\Exceptions\NoAPIResponseException;
use App\Http\Middleware\OctoAuthentication;
use Illuminate\Support\Facades\Request;
use App\Exceptions\NoMatchingDataException;
use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;
use stdClass;
use TourCMS\Utils\TourCMS;

class TourCMSService
{
    const ERROR_FAIL_SIG = 'FAIL_SIG';
    const NO_MATCHING_DATA = 'NO MATCHING DATA';
    const ERROR_PREVIOUSLY_CANCELLED = 'PREVIOUSLY CANCELLED';
    const ERROR_BOOKING_ALREADY_COMMITED = 'BOOKING ALREADY COMMITTED';
    const ERROR_OK = 'OK';
    const DEFAULT_API_BASE_URL = 'https://api.tourcms.com';
    const RESPONSE_FORMAT_SIMPLEXML = 'simplexml';
    const LIST_TOURS_EXTENDED_TOUR_INFO_PARAM = 'extended_tour_info=1';
    const SHOW_TOUR_DEPARTURES_CLOSED_PARAM = 'show_closed_departures=true';
    const NO_REQUEST_TO_PROCESS = 'NO_REQUEST_TO_PROCESS';

    private TourCMS $tourCMS;
    private TourCMSMulti $tourCMSMulti;
    protected string $channelId;

    public function __construct(string $maid, string $APIKey)
    {
        $this->tourCMS = new TourCMS($maid, $APIKey, self::RESPONSE_FORMAT_SIMPLEXML);
        $this->tourCMS->set_base_url($this->getAPIBaseUrl());

        $this->tourCMSMulti = new TourCMSMulti($maid, $APIKey, self::RESPONSE_FORMAT_SIMPLEXML);
        $this->tourCMSMulti->set_base_url($this->getAPIBaseUrl());

        $this->channelId = Request::get(OctoAuthentication::FIELD_CHANNEL_ID);
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

    public function showTour(string $tourId): SimpleXMLElement
    {
        $response = $this->tourCMS->show_tour($tourId, $this->channelId);
        $response = $this->handleResponse($response);

        return $response; 
    }

    public function checkAvailability(string $params, string $tourId): SimpleXMLElement
    {
        $response = $this->tourCMS->check_tour_availability($params, $tourId, $this->channelId);
        $response = $this->handleResponse($response);

        return $response;
    }

    public function showTourDepartures(string $tourId, string $startDate, string $endDate = '', string $extraParams = null): SimpleXMLElement
    {
        $queryString = self::SHOW_TOUR_DEPARTURES_CLOSED_PARAM;
        
        if (!empty($endDate)) {
            $queryString .= "&start_date_start={$startDate}&start_date_end={$endDate}";
        } else {
            $queryString .= "&start_date_start={$startDate}&start_date_end={$startDate}";
        }

        if (!empty($extraParams)) {
            if (substr($extraParams, 0, 1) != '&') {
                $queryString .= '&';
            }

            $queryString .= $extraParams;
        }

        
        $response = $this->tourCMS->show_tour_departures($tourId, $this->channelId, $queryString);
        $response = $this->handleResponse($response); 

        return $response;
    }

    public function multiCheckAvail(string $tourId, string $startDate, string $endDate, string $ratesQueryString): array
    {
        $requestHandler = new stdClass;
        $requestHandler->requestArray = [];
        
        $startDateTime = new DateTime($startDate);
        $endDateTime = new DateTime($endDate);
        $endDateTime->setTime(0,0,1);

        $interval = DateInterval::createFromDateString('1 day');
        $period = new DatePeriod($startDateTime, $interval, $endDateTime);


        foreach ($period as $dateTime) {
            $date = $dateTime->format("Y-m-d");
            $params = "date={$date}&{$ratesQueryString}";
            $requestHandler->requestArray[$date] = $this->tourCMSMulti->check_tour_availability($params, $tourId, $this->channelId);
        }

        $responses = $this->tourCMSMulti->proccessRequests($requestHandler);

        return (array) $responses->requestArray ?? [];
        
    }

    public function startNewBooking(SimpleXMLElement $bookingData): SimpleXMLElement
    {
        $response = $this->tourCMS->start_new_booking($bookingData, $this->channelId);
        return $this->handleResponse($response); 
    }

    public function commitBooking(string $bookingId): SimpleXMLElement
    {
        $bookingData = new SimpleXMLElement('<booking />');
        $bookingData->addChild('booking_id', $bookingId);
        $response = $this->tourCMS->commit_new_booking($bookingData, $this->channelId);
        return $this->handleResponse($response);
    }

    public function showBooking(string $bookingId): SimpleXMLElement
    {
        $response = $this->tourCMS->show_booking($bookingId, $this->channelId);
        return $this->handleResponse($response);
    }

    public function updateCustomer(SimpleXMLElement $customerXML): SimpleXMLElement
    {
        $response = $this->tourCMS->update_customer($customerXML, $this->channelId);
        return $this->handleResponse($response);
    }

    public function cancelBooking(SimpleXMLElement $bookingData): SimpleXMLElement
    {
        $response = $this->tourCMS->cancel_booking($bookingData, $this->channelId);
        return $this->handleResponse($response);
    }

    public function getArrayFromXmlNode(SimpleXMLElement $parent, string $childName = ''): array
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
        if (!$response) throw new NoAPIResponseException();
        switch ((string)$response->error) {
            case self::ERROR_OK:
            case self::ERROR_PREVIOUSLY_CANCELLED:
            case self::ERROR_BOOKING_ALREADY_COMMITED:
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
