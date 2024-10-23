<?php

namespace App\Http\Controllers;

use App\Exceptions\APICallNotOKException;
use App\Http\Middleware\OctoAuthentication;
use App\Http\Responses\OctoResponse;
use App\Services\JSONLogService;
use App\Services\ProductService;
use App\Services\TourCMSService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ProductController extends Controller
{
    public const ENDPOINT_NAME = 'products';
    public TourCMSService $tourCMSService;
    public ProductService $productService;
    public JSONLogService $logger;
    public function __construct(Request $request, TourCMSService $tourCMSService, ProductService $productService)
    {
        $this->tourCMSService = $tourCMSService;
        $this->productService = $productService;
        $this->logger = $this->loadLogger($request);
    }

    public function index(): JsonResponse
    {
        try {
            $data = $this->productService->getProductList();
            return new JsonResponse($data, Response::HTTP_OK);
        } catch (\App\Exceptions\FailSignatureException) {
            return OctoResponse::FORBIDDEN();
        } catch (APICallNotOKException) {
            return OctoResponse::INTERNAL_SERVER_ERROR();
        } catch (Throwable) {
            return OctoResponse::INTERNAL_SERVER_ERROR();
        }
    }

    protected function loadLogger(Request $request)
    { 
        return new JSONLogService(
            $request->get(OctoAuthentication::FIELD_CHANNEL_ID),
            $request->get(OctoAuthentication::FIELD_MAID),
            self::ENDPOINT_NAME,
            $request->get(OctoAuthentication::FIELD_X_CORRELATION_ID),
            $request->get(OctoAuthentication::FIELD_X_REQUEST_ID)
        );
    }
}
