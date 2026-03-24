<?php

namespace App\Http\Controllers;

use App\Exceptions\AvailabilityRequestInvalidParamException;
use App\Exceptions\AvailabilityRequestMissingParamException;
use App\Facades\OctoRequestFacade;
use App\Factories\AvailabilityRequestFactory;
use App\Http\Middleware\OctoAuthentication;
use App\Http\Requests\OctoRequest;
use App\Http\Responses\OctoResponse;
use App\Models\Product;
use App\Services\AvailabilityService;
use App\Services\JSONLogService;
use App\Services\ProductService;
use App\Services\TourPromotionService;
use App\Services\UnitService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AvailabilityController extends Controller
{
    public const DEFAULT_MAX_UNITS = 10;

    public function __construct(
        public AvailabilityRequestFactory $availabilityRequestFactory, 
        public AvailabilityService $availabilityService, 
        public ProductService $productService, 
        public JSONLogService $logger,
        public TourPromotionService $tourPromotionService) {}

    public function index(Request $request): JsonResponse
    {
        try {

            $requestParams = $request->post();
            $requestParams['channelId'] = $request->get(OctoAuthentication::FIELD_CHANNEL_ID);

            $this->availabilityService->validateRequestParams($requestParams);

            $productId = $request->post('productId');
            $product = $this->productService->find(productId: $productId);

            if ($product->getPricingType() === Product::PRICING_TYPE_VOLUME && isset($requestParams['units'])) {
                UnitService::validateQuantityBasedUnits($requestParams['units']);
            }

            if (OctoRequestFacade::isCapabilityActive(OctoRequest::CAPABILITIES_BOOKINGCOM_RATES)) {
                $rateId = $requestParams[OctoRequest::RATE_ID] ?? null;
                $this->tourPromotionService->validateRateId($product->getTourId(), $rateId);
            }

            $availabilityRequest = $this->availabilityRequestFactory->get($product, $requestParams);
            $availabilityIds = $request->get(AvailabilityService::PARAM_AVAILABILITY_IDS) ?? [];
            
            $availabilityRequest->setAvailabilityIds($availabilityIds);
            $availabilityRequest->setTourName($product->getInternalName());

            $optionId = $request->get(AvailabilityService::PARAM_OPTION_ID);
            $option = $product->getOptionById($optionId);

            $availabilityRequest->setMaxUnits($option->getRestrictions()->maxUnits ?? self::DEFAULT_MAX_UNITS);

            $availabilities = $this->availabilityService->getAvailabilities($availabilityRequest);
            $availabilitiesData = $this->availabilityService->getAvailabilitiesTransformed($availabilities);

            return new JsonResponse($availabilitiesData, Response::HTTP_OK);

        } catch (AvailabilityRequestMissingParamException $e) {
            return OctoResponse::BAD_REQUEST($e->getMessage());
        } catch (AvailabilityRequestInvalidParamException $e){
            return OctoResponse::BAD_REQUEST($e->getMessage());
        }
    }
}