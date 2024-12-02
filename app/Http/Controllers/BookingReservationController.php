<?php

namespace App\Http\Controllers;

use App\Http\Middleware\OctoAuthentication;
use App\Http\Requests\OctoRequest;
use App\Services\AvailabilityService;
use App\Services\BookingReservationService;
use App\Services\OptionService;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class BookingReservationController extends Controller
{
    public function __construct(
        public BookingReservationService $bookingService,
        public ProductService $productService,
        public AvailabilityService $availabilityService,
        public OptionService $optionService
    ) 
    {

    }

    public function index(Request $request): JsonResponse
    {
        $requestParams = $request->post();
        $channelId = $request->get(OctoAuthentication::FIELD_CHANNEL_ID);

        $this->validateRequestParams($requestParams, $channelId);

        return new JsonResponse([], Response::HTTP_OK);
    }

    protected function validateRequestParams(array $params, string $channelId)
    {

        $productId = $params[OctoRequest::PRODUCT_ID] ?? '';
        $this->productService->validateProductId($productId, $channelId);

        $optionId = $params[OctoRequest::OPTION_ID] ?? '';
        $this->optionService->validateOptionId($optionId);

        $availabilityId = $params[OctoRequest::AVAILABILITY_ID] ?? '';
    }
}