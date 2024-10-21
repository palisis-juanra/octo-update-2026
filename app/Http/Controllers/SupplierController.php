<?php

namespace App\Http\Controllers;

use App\Http\Middleware\OctoAuthentication;
use App\Services\JSONLogService;
use App\Services\TourCMSService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SupplierController extends Controller
{
    public const ENDPOINT_NAME = 'supplier';
    public TourCMSService $tourCMSService;
    public JSONLogService $logger;

    public function __construct(TourCMSService $tourCMSService, Request $request)
    {
        $this->tourCMSService = $tourCMSService;
        $this->logger = $this->loadLogger($request);
    }

    public function index(): JsonResponse
    {
        return new JsonResponse([], Response::HTTP_OK);
    }

    protected function loadLogger(Request $request)
    {
        
        return new JSONLogService($request->get(OctoAuthentication::FIELD_CHANNEL_ID), $request->get(OctoAuthentication::FIELD_MAID), self::ENDPOINT_NAME);
    }
}
