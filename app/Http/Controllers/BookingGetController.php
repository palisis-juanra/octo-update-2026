<?php

namespace App\Http\Controllers;

use App\Exceptions\APICallNotOKException;
use App\Exceptions\InvalidBookingUUIDException;
use App\Exceptions\NoMatchingDataException;
use App\Http\Responses\OctoResponse;
use App\Models\Booking;
use App\Services\BookingService;
use App\Services\JSONLogService;
use App\Transformers\BaseTransformer;
use App\Transformers\BookingTransformer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class BookingGetController extends Controller
{
    public const ENDPOINT_NAME = 'bookings';

    public BookingService $bookingService;
    public BookingTransformer $transformer;
    public JSONLogService $logger;

    public function __construct(BookingService $bookingService, JSONLogService $logger)
    {
        $this->logger = $logger;
        $this->bookingService = $bookingService;
        $this->transformer = new BookingTransformer(BaseTransformer::FULL_TRANSFORM);
    }

    public function show(string $bookingUUID): JsonResponse
    {
        try {
            $this->logger->info(["message" => "Starting to process get booking request", "uuid" => $bookingUUID]);
            $bookingByUUID = $this->bookingService->getBookingByUuid($bookingUUID);
            $bookingXMLResponse = $this->bookingService->getBooking($bookingByUUID, true);
            $transformedBooking = $this->transformer->transform($bookingXMLResponse);

            $originalBookingJSON = json_decode($bookingByUUID->complete_booking_json, true);
            $transformedBooking['contact'] = $originalBookingJSON['contact'];

            $bookingByUUID->update(['complete_booking_json' => json_encode($transformedBooking)]);
            $this->logger->info(["message" => "Request processed, returning response", "response" => $transformedBooking]);
            return new JsonResponse($transformedBooking, Response::HTTP_OK);
        } catch (\App\Exceptions\FailSignatureException) {
            return OctoResponse::FORBIDDEN();
        } catch (InvalidBookingUUIDException $e) {
            return OctoResponse::INVALID_BOOKING_UUID($bookingUUID);
        } catch (NoMatchingDataException) {
            return OctoResponse::INVALID_BOOKING_UUID($bookingUUID);
        } catch (APICallNotOKException) {
            return OctoResponse::INTERNAL_SERVER_ERROR();
        }
    }
}