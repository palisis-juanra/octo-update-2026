<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidAvailabilityIdException;
use App\Http\Middleware\OctoAuthentication;
use App\Http\Requests\OctoRequest;
use App\Services\AvailabilityService;
use App\Services\BookingReservationService;
use App\Services\JSONLogService;
use App\Services\OptionService;
use App\Services\ProductService;
use App\Services\UnitService;
use App\Transformers\BaseTransformer;
use App\Transformers\BookingTransformer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class BookingReservationController extends Controller
{
    public const FIELD_NOTES = 'notes';
    public BookingTransformer $transformer;
    public function __construct(
        public BookingReservationService $bookingService,
        public ProductService $productService,
        public AvailabilityService $availabilityService,
        public OptionService $optionService,
        public UnitService $unitService,
        public JSONLogService $logger
    ) 
    {
        $this->transformer = new BookingTransformer(BaseTransformer::FULL_TRANSFORM);
    }

    public function index(Request $request): JsonResponse
    {
        $requestParams = $request->post();
        $this->logger->info(["message" => "Starting to process request", "request" => $request->post()]);
        $channelId = $request->get(OctoAuthentication::FIELD_CHANNEL_ID);

        $this->validateRequestParams($requestParams, $channelId);

        $productId = $requestParams[OctoRequest::PRODUCT_ID];
        $product = $this->productService->find($productId);

        $optionId = $requestParams[OctoRequest::OPTION_ID];
        $option = $product->getOptionById($optionId);

        $availabilityId = $requestParams[OctoRequest::AVAILABILITY_ID];
        $availability = $this->availabilityService->find($availabilityId);

        $unitItems = $requestParams[OctoRequest::UNIT_ITEMS] ?? null;
        $uuid = $requestParams[OctoRequest::UUID] ?? null;
        $notes = $requestParams[self::FIELD_NOTES] ?? '';

        $booking = $this->bookingService->reserve($product, $option, $availability, $unitItems, $uuid, $notes);
        $bookingData = $this->transformer->transform($booking);

        return new JsonResponse($bookingData, Response::HTTP_OK);
    }

    protected function validateRequestParams(array $params, string $channelId)
    {

        $productId = $params[OctoRequest::PRODUCT_ID] ?? '';
        $this->productService->validateProductId($productId, $channelId);

        $optionId = $params[OctoRequest::OPTION_ID] ?? '';
        $this->optionService->validateOptionId($optionId);

        $availabilityId = $params[OctoRequest::AVAILABILITY_ID] ?? '';
        if (empty($availabilityId)){
            throw new InvalidAvailabilityIdException($availabilityId);
        }

        $unitItems = $params[OctoRequest::UNIT_ITEMS] ?? [];
        $this->unitService->validateUnitItems($unitItems);

    }
}