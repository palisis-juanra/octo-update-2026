<?php

namespace App\Http\Controllers;

use App\Http\Requests\OctoRequest;
use App\Services\BookingConfirmationService;
use App\Transformers\BaseTransformer;
use App\Transformers\BookingTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BookingConfirmationController
{
    public BookingTransformer $transformer;

    public function __construct(public BookingConfirmationService $service)
    {
        $this->transformer = new BookingTransformer(BaseTransformer::FULL_TRANSFORM);
    }

    public function index(Request $request, string $uuid): JsonResponse
    {
        $contact = $request->post(OctoRequest::CONTACT);

        $booking = $this->service->getBookingByUuid($uuid);
        $booking = $this->service->confirmBooking($booking);
        // if contact update lead customer
        if (!empty($contact)) {
            $this->service->addContactToBooking($contact, $booking);
        }
        $bookingData = $this->transformer->transform($booking);

        return new JsonResponse($bookingData, Response::HTTP_OK);
    }
}