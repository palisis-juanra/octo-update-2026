<?php

namespace App\Services;

use App\Exceptions\APICallNotOKException;
use App\Exceptions\APIThrottleError;
use App\Exceptions\BookingAlreadyRedeemedException;
use App\Exceptions\FailSignatureException;
use App\Exceptions\NoAPIResponseException;
use App\Exceptions\FailPermissionException;
use App\Exceptions\SupplierSubsystemError;
use App\Exceptions\TooManyDeparturesException;
use App\Exceptions\NoMatchingDataException;
use App\Facades\JSONLog;
use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use SimpleXMLElement;
use stdClass;
use TourCMS\Utils\TourCMS;

class TourCMSService
{
    // Cache
    public const string CACHE_REDIS_KEY_SHOW_CHANNEL = 'SHOW_CHANNEL|';
    public const string CACHE_REDIS_KEY_SHOW_TOUR = 'SHOW_TOUR|';
    public const string CACHE_REDIS_KEY_GET_TOUR_PROMOTIONS = 'GET_TOUR_PROMOTIONS|';
    public const int CACHE_TIME_SHOW_CHANNEL = 600;
    public const int CACHE_TIME_SHOW_TOUR = 300;
    public const int CACHE_TIME_GET_TOUR_PROMOTIONS = 300;

    // Errors
    public const ERROR_FAIL_SIG = 'FAIL_SIG';
    public const ERROR_FAIL_KEYNOTFOUND = 'FAIL_KEYNOTFOUND';
    public const NO_MATCHING_DATA = 'NO MATCHING DATA';
    public const ERROR_PREVIOUSLY_CANCELLED = 'PREVIOUSLY CANCELLED';
    public const ERROR_BOOKING_ALREADY_REDEEMED = 'BOOKING_ALREADY_REDEEMED';
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
    public const string ERROR_API_THROTTLE = 'API throttle';
    public const int MAX_SHOW_TOUR_DEPARTURES_COUNT = 500;

    public const string HEADER_X_CORRELATION_ID = 'X-Correlation-Id';

    protected int $maid;
    protected TourCMS $tourCMS;
    protected TourCMSMulti $tourCMSMulti;
    protected string $channelId;
    protected JSONLogService $jsonLogService;
    protected CacheRepository $cache;

    public function __construct(string $maid, string $APIKey, string $channelId, JSONLogService $jsonLogService, CacheRepository $cache)
    {
        $this->maid = (int) $maid;

        $this->tourCMS = new TourCMS($maid, $APIKey, self::RESPONSE_FORMAT_SIMPLEXML);
        $this->tourCMS->set_base_url($this->getAPIBaseUrl());
        $this->tourCMS->set_user_agent(self::OCTO_USER_AGENT);

        $this->tourCMSMulti = new TourCMSMulti($maid, $APIKey, self::RESPONSE_FORMAT_SIMPLEXML);
        $this->tourCMSMulti->set_base_url($this->getAPIBaseUrl());

        $this->channelId = $channelId;
        $this->jsonLogService = $jsonLogService;
        $this->cache = $cache;
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
            $cachedShowTour = $this->cache->get($redisKey);
            
            if (!empty($cachedShowTour)) {
                return simplexml_load_string($cachedShowTour);
            }
        }

        $this->tourCMS->add_header(self::HEADER_X_CORRELATION_ID, $this->jsonLogService->getLogId());
        $response = $this->tourCMS->show_tour($tourId, $channelId);
        $response = $this->handleResponse($response);
        
        $this->cache->put($redisKey, $response->asXML(), self::CACHE_TIME_SHOW_TOUR);
        
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
        $queryString .= '&per_page='.self::MAX_SHOW_TOUR_DEPARTURES_COUNT;

        if (!empty($extraParams)) {
            if (substr($extraParams, 0, 1) != '&') {
                $queryString .= '&';
            }

            $queryString .= $extraParams;
        }

        $this->tourCMS->add_header(self::HEADER_X_CORRELATION_ID, JSONLog::getLogId());
        JsonLog::info("Calling show tour departures for Tour {$tourId}, channel {$this->channelId}");
        $response = $this->tourCMS->show_tour_departures($tourId, $this->channelId, $queryString);
        $response = $this->handleResponse($response);
        $departureCount = (int) $response->tour->dates_and_prices->total_departure_count ?? 0;
        if ($departureCount > self::MAX_SHOW_TOUR_DEPARTURES_COUNT) {
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

    public function getTourPromotions(int $tourId, bool $cached = true): SimpleXMLElement
    {
        $endpoint = '/api/tours/promotions/get.xml?tour_id=' . $tourId;

        $redisKey = self::CACHE_REDIS_KEY_GET_TOUR_PROMOTIONS . "{$this->maid}|{$tourId}|{$this->channelId}";
        
        if (true === $cached) {
            $cachedGetTourPromotions = $this->cache->get($redisKey);
            
            if (!empty($cachedGetTourPromotions)) {
                return simplexml_load_string($cachedGetTourPromotions);
            }
        }

        $this->tourCMS->add_header(self::HEADER_X_CORRELATION_ID, $this->jsonLogService->getLogId());
        $response = $this->tourCMS->request($endpoint, $this->channelId);
        $this->handleResponse($response);
        
        $this->cache->put($redisKey, $response->asXML(), self::CACHE_TIME_GET_TOUR_PROMOTIONS);
        
        return $response;
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

    public function setTourCMS(TourCMS $tourCMS): self
    {
        $this->tourCMS = $tourCMS;
        return $this;
    }

    /* PROTECTED METHODS */

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
            case self::ERROR_BOOKING_ALREADY_REDEEMED:
                throw new BookingAlreadyRedeemedException();
            case self::NO_MATCHING_DATA:
                throw new NoMatchingDataException();
            case self::ERROR_PERM:
                throw new FailPermissionException();
            case self::ERROR_SUPPLIER_SUBSYSTEM_ERROR:
                throw new SupplierSubsystemError((string) $response->supplier_subsystem_error ?? '');
            case self::ERROR_API_THROTTLE:
                throw new APIThrottleError();
            default:
                throw new APICallNotOKException();
        }
    }
}
