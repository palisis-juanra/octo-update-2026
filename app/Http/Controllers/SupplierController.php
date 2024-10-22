<?php

namespace App\Http\Controllers;

use App\Exceptions\APICallNotOkException;
use App\Http\Middleware\OctoAuthentication;
use App\Http\Responses\OctoResponse;
use App\Services\JSONLogService;
use App\Services\SupplierService;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SupplierController extends Controller
{
    public const ENDPOINT_NAME = 'supplier';
    public SupplierService $supplierService;
    public JSONLogService $logger;

    public function __construct(Request $request, SupplierService $supplierService)
    {
        $this->supplierService = $supplierService;
        $this->logger = $this->loadLogger($request);
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $channelId = $request->get(OctoAuthentication::FIELD_CHANNEL_ID);
            $supplierData = $this->supplierService->getSupplierData($channelId);

            return new JsonResponse($supplierData, Response::HTTP_OK);
        } catch (\App\Exceptions\FailSignatureException) {
            return OctoResponse::FORBIDDEN();
        } catch (APICallNotOkException) {
            return OctoResponse::INTERNAL_SERVER_ERROR();
        } catch (Exception) {
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
