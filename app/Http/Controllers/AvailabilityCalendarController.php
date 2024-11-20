<?php

namespace App\Http\Controllers;

use App\Exceptions\AvailabilityRequestInvalidParamException;
use App\Exceptions\AvailabilityRequestMissingParamException;
use App\Exceptions\NoMatchingDataException;
use App\Http\Middleware\OctoAuthentication;
use App\Http\Responses\OctoResponse;
use App\Services\AvailabilityCalendarService;
use App\Services\AvailabilityService;
use App\Services\JSONLogService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AvailabilityCalendarController extends Controller
{
    public AvailabilityCalendarService $availabilityCalendarService;
    public AvailabilityService $availabilityService;
    public JSONLogService $logger;

    public function __construct(AvailabilityCalendarService $availabilityCalendarService, AvailabilityService $availabilityService, JSONLogService $logger)
    {
        $this->availabilityCalendarService = $availabilityCalendarService;
        $this->availabilityService = $availabilityService;
        $this->logger = $logger;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $requestParams = $request->post();
            $requestParams['channelId'] = $request->get(OctoAuthentication::FIELD_CHANNEL_ID);

            $this->availabilityService->validateRequestParams($requestParams);
            
            $octoCapabilities = $request->header('OctoCapabilities') ?? '';
            $availabilityRequest = $this->availabilityService->getAvailabilityRequest($requestParams, $octoCapabilities);

            $calendar = $this->availabilityCalendarService->getCalendar($availabilityRequest);

            $calendarData = $this->availabilityCalendarService->getAvailabilityCalendarTransformed($calendar);

            return new JsonResponse($calendarData, Response::HTTP_OK);

        } catch (AvailabilityRequestMissingParamException $e) {
            return OctoResponse::BAD_REQUEST($e->getMessage());
        } catch (AvailabilityRequestInvalidParamException $e){
            return OctoResponse::BAD_REQUEST($e->getMessage());
        } catch (NoMatchingDataException $e) {
            return new JsonResponse([], Response::HTTP_OK);
        }
    }
}