<?php

namespace App\Exceptions;

use App\Facades\JSONLog;
use App\Http\Responses\OctoResponse;
use App\Services\JSONLogService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class CustomExceptionHandler extends Handler
{
    public Request $request;
    public JSONLogService $logger;

    public function __construct(Container $container)
    {
        parent::__construct($container);
    }

    public function report(Throwable $exception)
    {
        // If you realize an exception doesnt being captured
        // just uncomment line below to let default exception
        // handler report the exception

        // parent::report($exception);
    }

    public function render($request, Throwable $exception): JsonResponse
    {
 
        $exceptionClass = get_class($exception);

        switch ($exceptionClass) {
            case (NotFoundHttpException::class):
                return OctoResponse::NOT_IMPLEMENTED();

            case (MethodNotAllowedHttpException::class):
                return OctoResponse::BAD_REQUEST('Invalid http request method');
            case (FailSignatureException::class): 
                return OctoResponse::FORBIDDEN();

            case (InvalidProductIdException::class):
            case (InvalidProductContentException::class):
                return OctoResponse::INVALID_PRODUCT_ID($exception->productId, $exception->getMessage());

            case (InvalidOptionIdException::class):
                return OctoResponse::INVALID_OPTION_ID($exception->optionId);
            
            case (InvalidUnitIdException::class):
                return OctoResponse::INVALID_UNIT_ID($exception->unitId);

            case (InvalidAvailabilityIdException::class):
                return OctoResponse::INVALID_AVAILABILITY_ID($exception->availabilityId);
            
            case (NoAvailabilityException::class):
                return OctoResponse::UNPROCESSABLE_ENTITY($exception->getMessage());

            case (NoMatchingDataException::class):
                return OctoResponse::INVALID_PRODUCT_ID($exception->productId);
            
            case (BadRequestException::class):
                return OctoResponse::BAD_REQUEST($exception->getMessage());

            case (APICallNotOKException::class):

                try {
                    JSONLog::error(["message" => "TourCMS API Call error"]);
                } catch (BindingResolutionException) {
                    parent::report($exception);
                }
                
                return OctoResponse::INTERNAL_SERVER_ERROR();

            default:
                try  {
                    JSONLog::error(["message" => "Exception captured by custom error handler", "exception" => $exception]);
                    return OctoResponse::INTERNAL_SERVER_ERROR();
                } catch (BindingResolutionException) {
                    parent::report($exception);
                    return new JsonResponse([], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
                
        }
        
    }
}