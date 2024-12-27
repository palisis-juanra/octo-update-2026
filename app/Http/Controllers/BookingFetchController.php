<?php

namespace App\Http\Controllers;

use App\Exceptions\APICallNotOKException;
use App\Exceptions\InvalidBookingUUIDException;
use App\Exceptions\NoMatchingDataException;
use App\Http\Responses\OctoResponse;
use App\Services\BookingConfirmationService;
use App\Services\JSONLogService;
use App\Transformers\BaseTransformer;
use App\Transformers\BookingTransformer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class BookingFetchController extends Controller
{
    public const ENDPOINT_NAME = 'bookings';
    public BookingConfirmationService $bookingService;
    public BookingTransformer $transformer;
    public JSONLogService $logger;
    public function __construct(BookingConfirmationService $bookingService, JSONLogService $logger)
    {
        $this->logger = $logger;
        $this->bookingService = $bookingService;
        $this->transformer = new BookingTransformer(BaseTransformer::FULL_TRANSFORM);
    }
    
    public function show(string $bookingUUID): JsonResponse
    {
        try {
            $bookingByUUID = $this->bookingService->getBookingByUuid($bookingUUID);
            $bookingXMLResponse = $this->bookingService->getBooking($bookingByUUID);

            return new JsonResponse($this->transformer->transform($bookingXMLResponse), Response::HTTP_OK);
        } catch (\App\Exceptions\FailSignatureException) {
            return OctoResponse::FORBIDDEN();
        } catch (InvalidBookingUUIDException $e) {
            return OctoResponse::INVALID_BOOKING_UUID($bookingUUID, $e->getMessage());
        } catch (NoMatchingDataException) {
            return OctoResponse::INVALID_BOOKING_UUID($bookingUUID);
        } catch (APICallNotOKException) {
            return OctoResponse::INTERNAL_SERVER_ERROR();
        }
    }
}