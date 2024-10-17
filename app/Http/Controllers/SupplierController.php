<?php

namespace App\Http\Controllers;

use App\Services\TourCMSService;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SupplierController extends Controller
{
    public TourCMSService $tourCMSService;
    public function __construct(TourCMSService $tourCMSService)
    {
        $this->tourCMSService = $tourCMSService;
    }

    public function index(Request $request): Response
    {
        return new JsonResponse([], Response::HTTP_OK);
    }
}
