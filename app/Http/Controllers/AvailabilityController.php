<?php

namespace App\Http\Controllers;

use App\Exceptions\APICallNotOKException;
use App\Exceptions\AvailabilityRequestInvalidParamException;
use App\Exceptions\AvailabilityRequestMissingParamException;
use App\Exceptions\InvalidOptionIdException;
use App\Exceptions\InvalidProductIdException;
use App\Http\Middleware\OctoAuthentication;
use App\Http\Responses\OctoResponse;
use App\Services\AvailabilityService;
use App\Services\JSONLogService;
use App\Services\ProductService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AvailabilityController extends Controller
{
    public AvailabilityService $availabilityService;
    public ProductService $productService;
    public JSONLogService $logger;

    public function __construct(AvailabilityService $availabilityService, ProductService $productService, JSONLogService $logger)
    {
        $this->availabilityService = $availabilityService;
        $this->productService = $productService;
        $this->logger = $logger;
    }

    public function index(Request $request): JsonResponse
    {
        try {

            $requestParams = $request->post();
            $requestParams['channelId'] = $request->get(OctoAuthentication::FIELD_CHANNEL_ID);

            $this->availabilityService->validateRequestParams($requestParams);

            $octoCapabilities = $request->header('OctoCapabilities') ?? '';
            $availabilyRequest = $this->availabilityService->getAvailabilityRequest($requestParams, $octoCapabilities);

            $productId = $request->post('productId');
            $product = $this->productService->find($productId);
            
            //$this->logger->info($product);

            $availabilyRequest->setMaxUnits(999);
            $availabilyRequest->setCutoff('CUTOFF');

            $departures = $this->availabilityService->getAvailabilities($availabilyRequest);

            $departuresData = $this->availabilityService->getAvailabilitiesTransformed($departures);

            return new JsonResponse($departuresData, Response::HTTP_OK);

        } catch (AvailabilityRequestMissingParamException $e) {
            return OctoResponse::BAD_REQUEST($e->getMessage());
        } catch (AvailabilityRequestInvalidParamException $e){
            return OctoResponse::BAD_REQUEST($e->getMessage());
        }
    }
}