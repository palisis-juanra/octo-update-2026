<?php

namespace App\Http\Controllers;

use App\Exceptions\APICallNotOkException;
use App\Http\Middleware\OctoAuthentication;
use App\Http\Responses\OctoResponse;
use App\Services\JSONLogService;
use App\Services\SupplierService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use OpenApi\Attributes as OA;

class SupplierController extends Controller
{
    const SUPPLIER_PATH = '/supplier';
    const SUPPLIERS_PATH = '/suppliers';

    public const ENDPOINT_NAME = 'supplier';
    public SupplierService $supplierService;
    public JSONLogService $logger;

    public function __construct(SupplierService $supplierService, JSONLogService $logger)
    {
        $this->supplierService = $supplierService;
        $this->logger = $logger;
    }

    #[OA\Get(
        path: '/supplier',
        description: 'Returns the supplier and associated contact details.',
        responses: [
            new OA\Response(
                response: 200, 
                description: 'OK', 
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: 'id', type: 'string', description: 'Supplier ID.'),
                            new OA\Property(property: 'name', type: 'string',  description: 'Name the supplier uses to identify itself.'),
                            new OA\Property(property: 'endpoint', type: 'string',  description: 'Base URL that will be prepended to all other paths.'),
                            new OA\Property(
                                property: 'contact',
                                type: 'object',
                                description: 'Supplier contact details.', 
                                items: new OA\Items(
                                    properties: [
                                    new OA\Property(property: 'website', type: 'null|string', description: 'Website of the supplier.'),
                                    new OA\Property(property: 'email', type: 'null|string', description: 'The email support contact for the Supplier.'),
                                    new OA\Property(property: 'telephone', type: 'null|string', description: 'The phone support contact for the Supplier.'),
                                    new OA\Property(property: 'address', type: 'null|string', description: 'The mail address support contact for the Supplier.'),
                                    ],
                                )
                            ),
                        ],
                    )

                )
            ),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 500, description: 'Internal Server Error'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $channelId = $request->get(OctoAuthentication::FIELD_CHANNEL_ID);
        $supplierData = $this->supplierService->getSupplierData($channelId);
        if ($this->isSuppliersRequest(request()->getRequestUri())) $supplierData = [$supplierData];
        return new JsonResponse($supplierData, Response::HTTP_OK);
    }

    protected function isSuppliersRequest($path):bool
    {
        return $path == self::SUPPLIERS_PATH; 
    }
}