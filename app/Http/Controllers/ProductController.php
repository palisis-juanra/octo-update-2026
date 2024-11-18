<?php

namespace App\Http\Controllers;

use App\Exceptions\APICallNotOKException;
use App\Exceptions\InvalidProductContentException;
use App\Exceptions\NoMatchingDataException;
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
    public function __construct(TourCMSService $tourCMSService, ProductService $productService, JSONLogService $logger)
    {
        $this->tourCMSService = $tourCMSService;
        $this->productService = $productService;
        $this->logger = $logger;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $channelId = $request->get(OctoAuthentication::FIELD_CHANNEL_ID);
            $productList = $this->productService->getProductList($channelId);
            return new JsonResponse($this->productService->transformList($productList), Response::HTTP_OK);
        } catch (\App\Exceptions\FailSignatureException) {
            return OctoResponse::FORBIDDEN();
        } catch (APICallNotOKException) {
            return OctoResponse::INTERNAL_SERVER_ERROR();
        } catch (Throwable) {
            return OctoResponse::INTERNAL_SERVER_ERROR();
        }
    }

    public function show(Request $request, string $productId): JsonResponse
    {
        try {
            $channelId = $request->get(OctoAuthentication::FIELD_CHANNEL_ID);
            if (!$this->productService->validateProductId($productId, $channelId)) {
                return OctoResponse::INVALID_PRODUCT_ID($productId);
            }
            $product = $this->productService->find($productId);
            return new JsonResponse($this->productService->transform($product), Response::HTTP_OK);
        } catch (\App\Exceptions\FailSignatureException) {
            return OctoResponse::FORBIDDEN();
        } catch (InvalidProductContentException $e) {
            return OctoResponse::INVALID_PRODUCT_ID($productId, $e->getMessage());
        } catch (NoMatchingDataException) {
            return OctoResponse::INVALID_PRODUCT_ID($productId);
        } catch (APICallNotOKException) {
            return OctoResponse::INTERNAL_SERVER_ERROR();
        } catch (Throwable $e) {
            $this->logger->info($e->getMessage() . '\n' . $e->getTraceAsString());
            return OctoResponse::INTERNAL_SERVER_ERROR();
        }
    }
}
