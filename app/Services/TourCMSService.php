<?php

namespace App\Services;

use App\Exceptions\APICallNotOKException;
use App\Exceptions\FailSignatureException;
use App\Exceptions\NoAPIResponseException;
use App\Exceptions\FailPermissionException;
use App\Exceptions\SupplierSubsystemError;
use App\Exceptions\TooManyDeparturesException;
use App\Http\Middleware\OctoAuthentication;
use Illuminate\Support\Facades\Request;
use App\Exceptions\NoMatchingDataException;
use App\Facades\JSONLog;
use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Support\Facades\Cache;
use SimpleXMLElement;
use stdClass;
use TourCMS\Utils\TourCMS;

class TourCMSService
{
    // Cache
    public const CACHE_REDIS_KEY_SHOW_CHANNEL = 'SHOW_CHANNEL|';
    public const CACHE_REDIS_KEY_SHOW_TOUR = 'SHOW_TOUR|';
    public const CACHE_TIME_SHOW_CHANNEL = 600;
    public const CACHE_TIME_SHOW_TOUR = 300;

    // Errors
    public const ERROR_FAIL_SIG = 'FAIL_SIG';
    public const ERROR_FAIL_KEYNOTFOUND = 'FAIL_KEYNOTFOUND';
    public const NO_MATCHING_DATA = 'NO MATCHING DATA';
    public const ERROR_PREVIOUSLY_CANCELLED = 'PREVIOUSLY CANCELLED';
    public const ERROR_BOOKING_ALREADY_COMMITED = 'BOOKING ALREADY COMMITTED';
    public const ERROR_NO_DATA_CHANGED = 'NO DATA CHANGED';
    public const ERROR_OK = 'OK';
    public const DEFAULT_API_BASE_URL = 'https://api.tourcms.com';
    public const RESPONSE_FORMAT_SIMPLEXML = 'simplexml';
    public const LIST_TOURS_EXTENDED_TOUR_INFO_PARAM = 'extended_tour_info=1';
    public const SHOW_TOUR_DEPARTURES_CLOSED_PARAM = 'show_closed_departures=true';
    public const SHOW_TOUR_DATES_AND_DEALS_DISTINCT_START_DATE_PARAM = 'distinct_start_dates=1';
    public const NO_REQUEST_TO_PROCESS = 'NO_REQUEST_TO_PROCESS';
    public const INVALID_BOOKING_ID = 'INVALID BOOKING ID';
    public const QUERYSTRING_SHOW_TEMPORARY_BOOKINGS = "&show_temporary_bookings=1";
    public const ERROR_PERM = 'FAIL_PERM';
    public const OCTO_USER_AGENT = 'octo.tourcms.com';
    public const string ERROR_SUPPLIER_SUBSYSTEM_ERROR = 'SUPPLIER_SUBSYSTEM_ERROR';

    public const string HEADER_X_CORRELATION_ID = 'X-Correlation-Id';

    private TourCMS $tourCMS;
    private TourCMSMulti $tourCMSMulti;
    protected string $channelId;

    public function __construct(string $maid, string $APIKey)
    {
        $this->tourCMS = new TourCMS($maid, $APIKey, self::RESPONSE_FORMAT_SIMPLEXML);
        $this->tourCMS->set_base_url($this->getAPIBaseUrl());
        $this->tourCMS->set_user_agent(self::OCTO_USER_AGENT);

        $this->tourCMSMulti = new TourCMSMulti($maid, $APIKey, self::RESPONSE_FORMAT_SIMPLEXML);
        $this->tourCMSMulti->set_base_url($this->getAPIBaseUrl());

        $this->channelId = Request::get(OctoAuthentication::FIELD_CHANNEL_ID);
    }

    public function showChannel(?string $channelId = null, bool $cached = true): SimpleXMLElement
    {
        if (is_null($channelId)) {
            $channelId = $this->channelId;
        }

        $redisKey = self::CACHE_REDIS_KEY_SHOW_CHANNEL . $channelId;
        if (true === $cached) {

            $cachedShowChannel = Cache::driver('redis')->get($redisKey);
            
            if (!empty($cachedShowChannel)) {
                return simplexml_load_string($cachedShowChannel);
            }
        }
        
        $this->tourCMS->add_header(self::HEADER_X_CORRELATION_ID, JSONLog::getLogId());
        $response = $this->tourCMS->show_channel($channelId);
        $response = $this->handleResponse($response);
        
        Cache::driver('redis')->put($redisKey, $response->asXML(), self::CACHE_TIME_SHOW_CHANNEL);
        
        return $response;
    }

    public function listTours(string $channelId, string $params = ""): SimpleXMLElement
    {
        $this->tourCMS->add_header(self::HEADER_X_CORRELATION_ID, JSONLog::getLogId());
        $response = $this->tourCMS->list_tours($channelId, $params);
        $response = $this->handleResponse($response);
        return $response;
    }

    public function showTour(string $tourId, ?string $channelId = null, bool $cached = false): SimpleXMLElement
    {
        if (is_null($channelId)) {
            $channelId = $this->channelId;
        }

        $redisKey = self::CACHE_REDIS_KEY_SHOW_TOUR . $tourId . '|' . $channelId;
        
        if (true === $cached) {
            $cachedShowChannel = Cache::driver('redis')->get($redisKey);
            
            if (!empty($cachedShowChannel)) {
                return simplexml_load_string($cachedShowChannel);
            }
        }

        $this->tourCMS->add_header(self::HEADER_X_CORRELATION_ID, JSONLog::getLogId());
        $response = $this->tourCMS->show_tour($tourId, $channelId);
        $response = $this->handleResponse($response);
        
        Cache::driver('redis')->put($redisKey, $response->asXML(), self::CACHE_TIME_SHOW_TOUR);
        
        return $response; 
    }

    public function checkAvailability(string $params, string $tourId): SimpleXMLElement
    {
        $this->tourCMS->add_header(self::HEADER_X_CORRELATION_ID, JSONLog::getLogId());
        $response = $this->tourCMS->check_tour_availability($params, $tourId, $this->channelId);
        $response = $this->handleResponse($response);

        return $response;
    }

    public function showTourDepartures(string $tourId, string $startDate, string $endDate = '', ?string $extraParams = null): SimpleXMLElement
    {
        $queryString = self::SHOW_TOUR_DEPARTURES_CLOSED_PARAM;
        
        if (!empty($endDate)) {
            $queryString .= "&start_date_start={$startDate}&start_date_end={$endDate}";
        } else {
            $queryString .= "&start_date_start={$startDate}&start_date_end={$startDate}";
        }
        $queryString .= '&per_page=100';

        if (!empty($extraParams)) {
            if (substr($extraParams, 0, 1) != '&') {
                $queryString .= '&';
            }

            $queryString .= $extraParams;
        }

        $this->tourCMS->add_header(self::HEADER_X_CORRELATION_ID, JSONLog::getLogId());
        error_log(JSONLog::getLogId());
        $response = $this->tourCMS->show_tour_departures($tourId, $this->channelId, $queryString);
        $response = $this->handleResponse($response);
        $departureCount = (int) $response->tour->dates_and_prices->total_departure_count ?? 0;
        if ($departureCount > 100) {
            throw new TooManyDeparturesException($departureCount);
        }

        return $response;
    }

    public function showTourDatesAndDeals(string $tourId, string $startDate, string $endDate = '', ?string $extraParams = null): SimpleXMLElement
    {
        $queryString = self::SHOW_TOUR_DATES_AND_DEALS_DISTINCT_START_DATE_PARAM;

        if (!empty($endDate)) {
            $queryString .= "&startdate_start={$startDate}&startdate_end={$endDate}";
        } else {
            $queryString .= "&startdate_start={$startDate}&startdate_end={$startDate}";
        }

        if (!empty($extraParams)) {
            if (substr($extraParams, 0, 1) != '&') {
                $queryString .= '&';
            }

            $queryString .= $extraParams;
        }

        $this->tourCMS->add_header(self::HEADER_X_CORRELATION_ID, JSONLog::getLogId());
        $response = $this->tourCMS->show_tour_datesanddeals($tourId, $this->channelId, $queryString);
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
        $bookingData->associate_customers = 1;
        $this->tourCMS->add_header(self::HEADER_X_CORRELATION_ID, JSONLog::getLogId());
        $response = $this->tourCMS->start_new_booking($bookingData, $this->channelId);
        return $this->handleResponse($response); 
    }

    public function commitBooking(string $bookingId, ?string $agentRef = ''): SimpleXMLElement
    {
        $bookingData = new SimpleXMLElement('<booking />');
        $bookingData->addChild('booking_id', $bookingId);
        if (!empty($agentRef)) {
            $bookingData->addChild('agent_ref', $agentRef);
        }

        $this->tourCMS->add_header(self::HEADER_X_CORRELATION_ID, JSONLog::getLogId());
        $response = $this->tourCMS->commit_new_booking($bookingData, $this->channelId);
        return $this->handleResponse($response);
    }

    public function showBooking(string $bookingId): SimpleXMLElement
    {
        $this->tourCMS->add_header(self::HEADER_X_CORRELATION_ID, JSONLog::getLogId());
        $response = $this->tourCMS->show_booking($bookingId . self::QUERYSTRING_SHOW_TEMPORARY_BOOKINGS, $this->channelId);
        return $this->handleResponse($response);
    }

    public function updateCustomer(SimpleXMLElement $customerXML): SimpleXMLElement
    {
        $this->tourCMS->add_header(self::HEADER_X_CORRELATION_ID, JSONLog::getLogId());
        $response = $this->tourCMS->update_customer($customerXML, $this->channelId);
        return $this->handleResponse($response);
    }

    public function cancelBooking(SimpleXMLElement $bookingData): SimpleXMLElement
    {
        $this->tourCMS->add_header(self::HEADER_X_CORRELATION_ID, JSONLog::getLogId());
        $response = $this->tourCMS->cancel_booking($bookingData, $this->channelId);
        return $this->handleResponse($response);
    }

    public function deleteBooking(string $bookingId): SimpleXMLElement
    {
        $this->tourCMS->add_header(self::HEADER_X_CORRELATION_ID, JSONLog::getLogId());
        $response = $this->tourCMS->delete_booking($bookingId, $this->channelId);
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
            case self::ERROR_NO_DATA_CHANGED:
                if (!($response instanceof SimpleXMLElement)) {
                    $response = simplexml_load_string($response);
                }
                return $response;
            case self::ERROR_FAIL_SIG:
            case self::ERROR_FAIL_KEYNOTFOUND:
                throw new FailSignatureException();
            case self::NO_MATCHING_DATA:
                throw new NoMatchingDataException();
            case self::ERROR_PERM:
                throw new FailPermissionException();
            case self::ERROR_SUPPLIER_SUBSYSTEM_ERROR:
                throw new SupplierSubsystemError((string) $response->supplier_subsystem_error ?? '');
            default:
                throw new APICallNotOKException();
        }
    }
}
