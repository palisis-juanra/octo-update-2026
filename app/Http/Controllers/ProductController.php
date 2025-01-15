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
use OpenApi\Attributes as OA;

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

    #[OA\Get(
        path: '/products',
        description: 'Fetch the list of products.',
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        type: 'array',
                        items: new OA\Items (
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', pattern: '^([A-Z]{2}_\d+_\d+\|\d+)$', description: 'Product ID.', example: 'TE_1_2|143'),
                                new OA\Property(property: 'internalName', type: 'string', description: 'The name the supplier calls the product.'),
                                new OA\Property(property: 'reference', type: ['null', 'string'], description: 'An optional code this supplier might use to identify the product.'),
                                new OA\Property(property: 'locale', type: 'string', description: 'A language code indicating what language this product content is in.'),
                                new OA\Property(property: 'timeZone', type: 'string', description: 'The IANA TimeZone this product is located in.'),
                                new OA\Property(property: 'allowFreesale', type: 'boolean', description: 'Whether a booking can be made for this product without having to query availability first.'),
                                new OA\Property(property: 'instantConfirmation', type: 'boolean', description: 'Whether bookings will be immediatly confirmed when a sale is made.'),
                                new OA\Property(property: 'instantDelivery', type: 'boolean', description: 'Whether the Reseller can expect immediate delivery of the customers tickets.'),
                                new OA\Property(property: 'availabilityRequired', type: 'boolean', description: 'Whether an availabilityId is required when creating a booking.'),
                                new OA\Property(property: 'availabilityType', type: 'string', enum: ['START_TIME', 'OPENING_HOURS'], description: 'What type of availability this product has, possible values are: "START_TIME", "OPENING_HOURS"',),
                                new OA\Property(
                                    property: 'deliveryFormats',
                                    type: 'array',
                                    description: 'An array of formats the API will deliver the ticket as. Possible values are: "QRCODE", "PDF_URL".',
                                    items: new OA\Items(
                                        type: 'string',
                                        enum: ['QRCODE', 'PDF_URL']
                                    )
                                ),
                                new OA\Property(
                                    property: 'deliveryMethods',
                                    type: 'array',
                                    description: 'How the formats described in "deliveryFormats" will be delivered in the booking response. Possible values are: "TICKET", "VOUCHER".',
                                    items: new OA\Items(
                                        type: 'string',
                                        enum: ['TICKET', 'VOUCHER']
                                    )
                                ),
                                new OA\Property(property: 'redemptionMethod', type: 'string', enum: ['DIGITAL', 'PRINT', 'MANIFEST'], description: 'How the voucher can be redeemed. Possible values are: "DIGITAL", "PRINT", "MANIFEST".'),
                                new OA\Property(
                                    property: 'options',
                                    type: 'array',
                                    description: 'An array of all options for this product.',
                                    items: new OA\Items(
                                        type: 'object',
                                        properties: [
                                            new OA\Property(property: 'id', type: 'string', description: 'The id that identifies this option.'),
                                            new OA\Property(property: 'default', type: 'boolean', description: '"TRUE" identifies the option as default, and should therefore be rendered and selected first'),
                                            new OA\Property(property: 'internalName', type: 'string', description: 'The name the supplier calls the option by.'),
                                            new OA\Property(property: 'reference', type: ['null', 'string'], description: 'An optional code this supplier might use to identify the product.'),
                                            new OA\Property(
                                                property: 'availabilityLocalStartTimes',
                                                type: 'array',
                                                description: 'An array of all possible start times that can be returned during availability.',
                                                items: new OA\Items(
                                                    type: 'string',
                                                    pattern: '^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$'
                                                )
                                            ),
                                            new OA\Property(property: 'cancellationCutoff', type: 'string', description: 'How long before the tour the booking can still be cancelled.'),
                                            new OA\Property(property: 'cancellationCutoffAmount', type: 'integer', description: 'The numeric amount for the cutoff.'),
                                            new OA\Property(property: 'cancellationCutoffUnit', type: 'string', enum: ['hour', 'minute', 'day'], description: 'Time units used to determine duration. Three values are available: "hour", "minute", "day".'),
                                            new OA\Property(
                                                property: 'requiredContactFields',
                                                type: 'array',
                                                description: 'An array of the contact fields required to confirm a booking. These just apply to the lead traveller on the booking and not for every ticket.',
                                                items: new OA\Items(
                                                    type: 'string',
                                                    enum: ['firstName', 'lastName', 'phoneNumber']
                                                )
                                            ),
                                            new OA\Property(
                                                property: 'restrictions',
                                                type: 'object',
                                                description: 'An object containing a fixed list of restrictions for booking the option.',
                                                properties: [
                                                    new OA\Property(property: 'minUnits', type: ['null', 'integer'], description: 'The minimum number of tickets that can be purchased in a single booking.'),
                                                    new OA\Property(property: 'maxUnits', type: ['null', 'integer'], description: 'The maximum number of tickets that can be purchased in a single booking.')
                                                ]
                                            ),
                                            new OA\Property(
                                                property: 'units',
                                                type: 'array',
                                                description: 'The list of ticket types (units) available for sale.',
                                                items: new OA\Items(
                                                    type: 'object',
                                                    properties: [
                                                        new OA\Property(property: 'id', type: 'string', description: 'The id that identifies this unit. Must be unique within the scope of the option.'),
                                                        new OA\Property(property: 'internalName', type: 'string', description: 'A name to help with identifying the unit. It should not be shown to the customer.'),
                                                        new OA\Property(property: 'reference', type: ['null', 'string'], description: 'Internal reference identifier that the Supplier wishes to use.'),
                                                        new OA\Property(property: 'type', type: 'string', enum: ['ADULT', 'YOUTH', 'CHILD', 'INFANT', 'SENIOR'], description: 'Base unit type for this unit definition.'),
                                                        new OA\Property(
                                                            property: 'requiredContactFields',
                                                            type: 'array',
                                                            description: 'An array of the contact information per ticket that the supplier expects.',
                                                            items: new OA\Items(
                                                                type: 'string',
                                                                enum: ['firstName', 'lastName', 'phoneNumber']
                                                            )
                                                        ),
                                                        new OA\Property(
                                                            property: 'restrictions',
                                                            type: 'object',
                                                            description: 'Unit restrictions.',
                                                            properties: [
                                                                new OA\Property(property: 'minAge', type: 'integer', description: 'The minimum age this unit can be sold to.'),
                                                                new OA\Property(property: 'maxAge', type: 'integer', description: 'The maximum age this unit can be sold to.'),
                                                                new OA\Property(property: 'idRequired', type: 'boolean', description: 'Whether a form of identification will be required at the redemption point.'),
                                                                new OA\Property(property: 'minQuantity', type: ['null', 'integer'], description: 'If there is a minimum amount of units to be chosen for purchase.'),
                                                                new OA\Property(property: 'maxQuantity', type: ['null', 'integer'], description: 'If there is a maximum amount of units to be chosen for purchase.'),
                                                                new OA\Property(property: 'paxCount', type: 'integer', description: 'The amount of people each unit counts as.'),
                                                                new OA\Property(
                                                                    property: 'accompaniedBy',
                                                                    type: 'array',
                                                                    description: 'If the unit needs to be accompanied by another unit',
                                                                    items: new OA\Items(
                                                                        type: 'string'
                                                                    )
                                                                ),
                                                            ]
                                                        )
                                                    ]
                                                )
                                            )
                                        ],
                                    )
                                ),
                            ]
                        ),
                    )
                )
            ),
            new OA\Response(response: 400, description: 'Invalid Product Id'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 500, description: 'Internal Server Error')
        ]
    )]
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
        }
    }

    #[OA\Get(
        path: '/products/{id}',
        description: 'Fetch the product for the given id',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'Product ID.',
                required: 'true',
                schema: new OA\Schema(
                    type: 'string',
                    pattern: '^([A-Z]{2}_\d+_\d+\|\d+)$',
                    example: 'TE_1_2|143',
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: 'id', type: 'string', pattern: '^([A-Z]{2}_\d+_\d+\|\d+)$', description: 'Product ID.', example: 'TE_1_2|143'),
                            new OA\Property(property: 'internalName', type: 'string', description: 'The name the supplier calls the product.'),
                            new OA\Property(property: 'reference', type: ['null', 'string'], description: 'An optional code this supplier might use to identify the product.'),
                            new OA\Property(property: 'locale', type: 'string', description: 'A language code indicating what language this product content is in.'),
                            new OA\Property(property: 'timeZone', type: 'string', description: 'The IANA TimeZone this product is located in.'),
                            new OA\Property(property: 'allowFreesale', type: 'boolean', description: 'Whether a booking can be made for this product without having to query availability first.'),
                            new OA\Property(property: 'instantConfirmation', type: 'boolean', description: 'Whether bookings will be immediatly confirmed when a sale is made.'),
                            new OA\Property(property: 'instantDelivery', type: 'boolean', description: 'Whether the Reseller can expect immediate delivery of the customers tickets.'),
                            new OA\Property(property: 'availabilityRequired', type: 'boolean', description: 'Whether an availabilityId is required when creating a booking.'),
                            new OA\Property(property: 'availabilityType', type: 'string', enum: ['START_TIME', 'OPENING_HOURS'], description: 'What type of availability this product has, possible values are: "START_TIME", "OPENING_HOURS"',),
                            new OA\Property(
                                property: 'deliveryFormats',
                                type: 'array',
                                description: 'An array of formats the API will deliver the ticket as. Possible values are: "QRCODE", "PDF_URL".',
                                items: new OA\Items(
                                    type: 'string',
                                    enum: ['QRCODE', 'PDF_URL']
                                )
                            ),
                            new OA\Property(
                                property: 'deliveryMethods',
                                type: 'array',
                                description: 'How the formats described in "deliveryFormats" will be delivered in the booking response. Possible values are: "TICKET", "VOUCHER".',
                                items: new OA\Items(
                                    type: 'string',
                                    enum: ['TICKET', 'VOUCHER']
                                )
                            ),
                            new OA\Property(property: 'redemptionMethod', type: 'string', enum: ['DIGITAL', 'PRINT', 'MANIFEST'], description: 'How the voucher can be redeemed. Possible values are: "DIGITAL", "PRINT", "MANIFEST".'),
                            new OA\Property(
                                property: 'options',
                                type: 'array',
                                description: 'An array of all options for this product.',
                                items: new OA\Items(
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'string', description: 'The id that identifies this option.'),
                                        new OA\Property(property: 'default', type: 'boolean', description: '"TRUE" identifies the option as default, and should therefore be rendered and selected first'),
                                        new OA\Property(property: 'internalName', type: 'string', description: 'The name the supplier calls the option by.'),
                                        new OA\Property(property: 'reference', type: ['null', 'string'], description: 'An optional code this supplier might use to identify the product.'),
                                        new OA\Property(
                                            property: 'availabilityLocalStartTimes',
                                            type: 'array',
                                            description: 'An array of all possible start times that can be returned during availability.',
                                            items: new OA\Items(
                                                type: 'string',
                                                pattern: '^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$'
                                            )
                                        ),
                                        new OA\Property(property: 'cancellationCutoff', type: 'string', description: 'How long before the tour the booking can still be cancelled.'),
                                        new OA\Property(property: 'cancellationCutoffAmount', type: 'integer', description: 'The numeric amount for the cutoff.'),
                                        new OA\Property(property: 'cancellationCutoffUnit', type: 'string', enum: ['hour', 'minute', 'day'], description: 'Time units used to determine duration. Three values are available: "hour", "minute", "day".'),
                                        new OA\Property(
                                            property: 'requiredContactFields',
                                            type: 'array',
                                            description: 'An array of the contact fields required to confirm a booking. These just apply to the lead traveller on the booking and not for every ticket.',
                                            items: new OA\Items(
                                                type: 'string',
                                                enum: ['firstName', 'lastName', 'phoneNumber']
                                            )
                                        ),
                                        new OA\Property(
                                            property: 'restrictions',
                                            type: 'object',
                                            description: 'An object containing a fixed list of restrictions for booking the option.',
                                            properties: [
                                                new OA\Property(property: 'minUnits', type: ['null', 'integer'], description: 'The minimum number of tickets that can be purchased in a single booking.'),
                                                new OA\Property(property: 'maxUnits', type: ['null', 'integer'], description: 'The maximum number of tickets that can be purchased in a single booking.')
                                            ]
                                        ),
                                        new OA\Property(
                                            property: 'units',
                                            type: 'array',
                                            description: 'The list of ticket types (units) available for sale.',
                                            items: new OA\Items(
                                                type: 'object',
                                                properties: [
                                                    new OA\Property(property: 'id', type: 'string', description: 'The id that identifies this unit. Must be unique within the scope of the option.'),
                                                    new OA\Property(property: 'internalName', type: 'string', description: 'A name to help with identifying the unit. It should not be shown to the customer.'),
                                                    new OA\Property(property: 'reference', type: ['null', 'string'], description: 'Internal reference identifier that the Supplier wishes to use.'),
                                                    new OA\Property(property: 'type', type: 'string', enum: ['ADULT', 'YOUTH', 'CHILD', 'INFANT', 'SENIOR'], description: 'Base unit type for this unit definition.'),
                                                    new OA\Property(
                                                        property: 'requiredContactFields',
                                                        type: 'array',
                                                        description: 'An array of the contact information per ticket that the supplier expects.',
                                                        items: new OA\Items(
                                                            type: 'string',
                                                            enum: ['firstName', 'lastName', 'phoneNumber']
                                                        )
                                                    ),
                                                    new OA\Property(
                                                        property: 'restrictions',
                                                        type: 'object',
                                                        description: 'Unit restrictions.',
                                                        properties: [
                                                            new OA\Property(property: 'minAge', type: 'integer', description: 'The minimum age this unit can be sold to.'),
                                                            new OA\Property(property: 'maxAge', type: 'integer', description: 'The maximum age this unit can be sold to.'),
                                                            new OA\Property(property: 'idRequired', type: 'boolean', description: 'Whether a form of identification will be required at the redemption point.'),
                                                            new OA\Property(property: 'minQuantity', type: ['null', 'integer'], description: 'If there is a minimum amount of units to be chosen for purchase.'),
                                                            new OA\Property(property: 'maxQuantity', type: ['null', 'integer'], description: 'If there is a maximum amount of units to be chosen for purchase.'),
                                                            new OA\Property(property: 'paxCount', type: 'integer', description: 'The amount of people each unit counts as.'),
                                                            new OA\Property(
                                                                property: 'accompaniedBy',
                                                                type: 'array',
                                                                description: 'If the unit needs to be accompanied by another unit',
                                                                items: new OA\Items(
                                                                    type: 'string'
                                                                )
                                                            ),
                                                        ]
                                                    )
                                                ]
                                            )
                                        )
                                    ],
                                )
                            ),
                        ],
                    )
                )
            ),
            new OA\Response(response: 400, description: 'Invalid Product Id'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 500, description: 'Internal Server Error')
        ]
    )]
    public function show(Request $request, string $productId): JsonResponse
    {
        try {
            $channelId = $request->get(OctoAuthentication::FIELD_CHANNEL_ID);
            if (!$this->productService->validateProductId($productId, $channelId)) {
                $this->logger->info("INVALID PRODUCT: {$productId}");
                return OctoResponse::INVALID_PRODUCT_ID($productId);
            }
            $product = $this->productService->find($productId);
            return new JsonResponse($this->productService->transform($product), Response::HTTP_OK);
        } catch (\App\Exceptions\FailSignatureException) {
            return OctoResponse::FORBIDDEN();
        } catch (InvalidProductContentException $e) {
            $this->logger->info("INVALID PRODUCT CONTENT: {$e->getFile()} ({$e->getLine()}");
            return OctoResponse::INVALID_PRODUCT_ID($productId, $e->getMessage());
        } catch (NoMatchingDataException $e) {
            $this->logger->info("NO MATCHING DATA: {$e->getFile()} ({$e->getLine()}");
            return OctoResponse::INVALID_PRODUCT_ID($productId);
        } catch (APICallNotOKException) {
            return OctoResponse::INTERNAL_SERVER_ERROR();
        }
    }
}
