<?php

namespace App\Http\Controllers;

use App\Exceptions\AvailabilityRequestInvalidParamException;
use App\Exceptions\AvailabilityRequestMissingParamException;
use App\Factories\AvailabilityRequestFactory;
use App\Http\Middleware\OctoAuthentication;
use App\Http\Responses\OctoResponse;
use App\Services\AvailabilityService;
use App\Services\JSONLogService;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AvailabilityController extends Controller
{
    public AvailabilityRequestFactory $availabilityRequestFactory;
    public AvailabilityService $availabilityService;
    public ProductService $productService;
    public JSONLogService $logger;

    public function __construct(AvailabilityRequestFactory $factory, AvailabilityService $service, ProductService $productService, JSONLogService $logger)
    {
        $this->availabilityRequestFactory = $factory;
        $this->availabilityService = $service;
        $this->productService = $productService;
        $this->logger = $logger;
    }

    public function index(Request $request): JsonResponse
    {
        try {

            $requestParams = $request->post();
            $requestParams['channelId'] = $request->get(OctoAuthentication::FIELD_CHANNEL_ID);

            $this->availabilityService->validateRequestParams($requestParams);

            $productId = $request->post('productId');
            $product = $this->productService->find($productId);

            $octoCapabilities = $request->header('Octo-Capabilities') ?? '';
            $availabilyRequest = $this->availabilityRequestFactory->get($requestParams, $octoCapabilities, $product->getMinBookingSize());
            
            $optionId = $request->get(AvailabilityService::PARAM_OPTION_ID);
            $option = $product->getOptionById($optionId);

            $availabilyRequest->setMaxUnits($option->restrictions->maxUnits ?? 10);
            $tourCMSCutoff = $product->getCutoff();
            $octoCutoff = $this->availabilityService->getCutoffFromTourCMSCutoff($tourCMSCutoff, $availabilyRequest->getLocalDateStart());
            $availabilyRequest->setCutoff($octoCutoff);

            $availabilities = $this->availabilityService->getAvailabilities($availabilyRequest);

            $availabilitiesData = $this->availabilityService->getAvailabilitiesTransformed($availabilities);

            return new JsonResponse($availabilitiesData, Response::HTTP_OK);

        } catch (AvailabilityRequestMissingParamException $e) {
            return OctoResponse::BAD_REQUEST($e->getMessage());
        } catch (AvailabilityRequestInvalidParamException $e){
            return OctoResponse::BAD_REQUEST($e->getMessage());
        }
    }
}