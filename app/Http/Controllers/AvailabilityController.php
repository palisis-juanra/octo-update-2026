<?php

namespace App\Http\Controllers;

use App\Exceptions\APICallNotOKException;
use App\Exceptions\InvalidOptionIdException;
use App\Exceptions\InvalidProductIdException;
use App\Http\Middleware\OctoAuthentication;
use App\Http\Responses\OctoResponse;
use App\Services\AvailabilityService;
use App\Services\ProductService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AvailabilityController extends Controller
{
    public AvailabilityService $availabilityService;
    public ProductService $productService;

    public function __construct(AvailabilityService $availabilityService, ProductService $productService)
    {
        $this->availabilityService = $availabilityService;
        $this->productService = $productService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $requestParams = $request->post();
            $requestParams['channelId'] = $request->get(OctoAuthentication::FIELD_CHANNEL_ID);
    
            $this->availabilityService->validateRequestParams($requestParams);
    
            $availabilyRequestType =   $this->availabilityService->getAvailabilityRequest($requestParams);
    
            $productId = $request->post('productId');
            $product = $this->productService->getProduct($productId);
            $departures =  $this->availabilityService->getDepartures($availabilyRequestType);
            $departuresData =  $this->availabilityService->getDeparturesData($departures);
    
    
            return new JsonResponse($departuresData, Response::HTTP_OK);

        } catch (\App\Exceptions\FailSignatureException) {
            return OctoResponse::FORBIDDEN();

        } catch (APICallNotOKException) {
            return OctoResponse::INTERNAL_SERVER_ERROR();

        } catch (InvalidProductIdException $e) {
            return OctoResponse::INVALID_PRODUCT_ID($e->productId);

        } catch (InvalidOptionIdException $e) {
            return OctoResponse::INVALID_OPTION_ID($e->optionId);

        } catch (Throwable $e) {
            Log::error($e->getMessage() . $e->getTraceAsString());
            return OctoResponse::INTERNAL_SERVER_ERROR($e->getMessage());
        }
        
    }
}