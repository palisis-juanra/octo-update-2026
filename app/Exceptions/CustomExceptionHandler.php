<?php

namespace App\Exceptions;

use App\Http\Responses\OctoResponse;
use App\Services\JSONLogService;
use Illuminate\Contracts\Container\Container;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;

class CustomExceptionHandler extends Handler
{
    public Request $request;
    public JSONLogService $logger;

    public function __construct(Container $container, Request $request, JSONLogService $logger)
    {
        parent::__construct($container);

        $this->request = $request;
        $this->logger = $logger;
    }

    public function report(Throwable $exception)
    {
        // When Laravel works in debug mode we will be reporting exceptions 
        // using Laravel custom exception handler too

        if (env('APP_DEBUG', true) === true) {
            parent::report($exception);
        }
    }

    public function render($request, Throwable $exception): JsonResponse
    {

        $exceptionClass = get_class($exception);

        switch ($exceptionClass) {

            case (FailSignatureException::class): 
                return OctoResponse::FORBIDDEN();

            case (InvalidProductIdException::class):
                return OctoResponse::INVALID_PRODUCT_ID($exception->getProductId(), $exception->getMessage());
            
            case (InvalidProductContentException::class):
                return OctoResponse::INVALID_PRODUCT_ID($exception->getProductId(), $exception->getMessage());

            case (NoMatchingDataException::class):
                return OctoResponse::INVALID_PRODUCT_ID($exception->getProductId());

            case (APICallNotOKException::class):
                $this->logger->error(["message" => "TourCMS API Call error"]);
                return OctoResponse::INTERNAL_SERVER_ERROR();

            default:
                $this->logger->error(["message" => "Exception captured by custom error handler", "exception" => $exception]);
                return OctoResponse::INTERNAL_SERVER_ERROR();

        }
        
    }
}