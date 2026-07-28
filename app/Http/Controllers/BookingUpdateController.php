<?php

namespace App\Http\Controllers;

use App\Services\BookingUpdateService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class BookingUpdateController extends Controller
{
    public function __construct(public BookingUpdateService $bookingUpdateService) {}

    public function update(Request $request, string $uuid): JsonResponse
    {
        $bookingData = $this->bookingUpdateService->update($uuid, $request->post());

        return new JsonResponse($bookingData, Response::HTTP_OK);
    }
}
