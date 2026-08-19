<?php

namespace App\Http\Controllers;

use App\Exceptions\AvailabilityRequestInvalidParamException;
use App\Exceptions\AvailabilityRequestMissingParamException;
use App\Exceptions\NoMatchingDataException;
use App\Http\Middleware\OctoAuthentication;
use App\Http\Responses\OctoResponse;
use App\Services\AvailabilityCalendarService;
use App\Services\JSONLogService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AvailabilityCalendarController extends Controller
{
    public AvailabilityCalendarService $availabilityCalendarService;

    public JSONLogService $logger;

    public function __construct(AvailabilityCalendarService $availabilityCalendarService, JSONLogService $logger)
    {
        $this->availabilityCalendarService = $availabilityCalendarService;
        $this->logger = $logger;
    }

    #[OA\Post(
        path: '/availability/calendar',
        description: 'Return a single availability object per day. Used to populate a calendar.',
        requestBody: new OA\RequestBody(
            description: 'Input data format',
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'productId', type: 'string', pattern: '^([A-Z]{2}_\d+_\d+\|\d+)$', description: 'Product ID.', example: 'TE_1_2|143'),
                        new OA\Property(property: 'optionId', type: 'string', pattern: '(^SINGLE)|(^(START_TIME\|([01][0-9]|2[0-3]):([0-5][0-9])$))|(^(DEPARTURE_CODE\|.*))|(^(SUPPLIER_NOTE\|.*))|(^(SUPPLIER_NOTE_PLUS_START_TIME\|.*))', description: 'Option ID.', example: 'SINGLE'),
                        new OA\Property(property: 'localDateStart', type: 'string', pattern: '^\d{4}\-(0[1-9]|1[012])\-(0[1-9]|[12][0-9]|3[01])$', description: 'Start date to query for (YYYY-MM-DD).', example: '2022-05-11'),
                        new OA\Property(property: 'localDateEnd', type: 'string', pattern: '^\d{4}\-(0[1-9]|1[012])\-(0[1-9]|[12][0-9]|3[01])$', description: 'End date to query for (YYYY-MM-DD).', example: '2022-05-18'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        type: 'array',
                        items: new OA\Items(
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'localDate', type: 'string', pattern: '^\d{4}\-(0[1-9]|1[012])\-(0[1-9]|[12][0-9]|3[01])$', description: 'A single date to query.', example: '2022-05-12'),
                                new OA\Property(property: 'available', type: 'boolean', description: 'Whether there is availability for this date / slot.'),
                                new OA\Property(property: 'status', type: 'string', description: 'The status of that date.', example: 'AVAILABLE'),
                                new OA\Property(property: 'vacancies', type: ['null', 'integer'], description: 'Total number of remaining vacancies in the option. Null if unlimited or not specified.'),
                                new OA\Property(property: 'capacity', type: ['null', 'integer'], description: 'The total capacity on this day. Null by default or if not specified.'),
                                new OA\Property(
                                    property: 'openingHours',
                                    type: 'array',
                                    description: 'An array containing the opening hours for this date.',
                                    items: new OA\Items(
                                        type: 'object',
                                        properties: [
                                            new OA\Property(property: 'from', type: 'string', pattern: '^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$', description: 'When this product opens (HH:MM).', example: '08:00'),
                                            new OA\Property(property: 'to', type: 'string', pattern: '^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$', description: 'When this product closes (HH:M).', example: '16:00'),
                                        ],
                                    )
                                ),
                            ]
                        ),
                    )
                )
            ),
            new OA\Response(response: 400, description: 'Invalid Product Id | Invalid Option Id'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 500, description: 'Internal Server Error'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        try {
            $requestParams = $request->post();
            $requestParams['channelId'] = $request->input(OctoAuthentication::FIELD_CHANNEL_ID);

            $this->availabilityCalendarService->validateRequestParams($requestParams);

            $calendar = $this->availabilityCalendarService->getCalendar($requestParams);

            $calendarData = $this->availabilityCalendarService->getAvailabilityCalendarTransformed($calendar);

            return new JsonResponse($calendarData, Response::HTTP_OK);

        } catch (AvailabilityRequestMissingParamException $e) {
            return OctoResponse::BAD_REQUEST($e->getMessage());
        } catch (AvailabilityRequestInvalidParamException $e) {
            return OctoResponse::BAD_REQUEST($e->getMessage());
        } catch (NoMatchingDataException $e) {
            return new JsonResponse([], Response::HTTP_OK);
        }
    }
}
