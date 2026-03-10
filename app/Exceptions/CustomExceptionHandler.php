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
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
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
                $response = OctoResponse::NOT_IMPLEMENTED();
                break;

            case (MethodNotAllowedHttpException::class):
                $response = OctoResponse::BAD_REQUEST('Invalid http request method');
                break;

            case (FailSignatureException::class): 
                $response = OctoResponse::FORBIDDEN();
                break;

            case (InvalidProductIdException::class):
            case (InvalidProductContentException::class):
                $response = OctoResponse::INVALID_PRODUCT_ID($exception->productId);
                break;

            case (InvalidOptionIdException::class):
                $response = OctoResponse::INVALID_OPTION_ID($exception->optionId);
                break;
                
            case (InvalidUnitIdException::class):
                $response = OctoResponse::INVALID_UNIT_ID($exception->unitId);
                break;

            case (InvalidAvailabilityIdException::class):
                $response = OctoResponse::INVALID_AVAILABILITY_ID($exception->availabilityId);
                break;

            case (BookingAlreadyRedeemedException::class):
            case (NoAvailabilityException::class):
            case (UnprocessableEntityHttpException::class):
                $response = OctoResponse::UNPROCESSABLE_ENTITY($exception->getMessage());
                break;

            case (BookingNotCancellableException::class):
                $response = OctoResponse::UNPROCESSABLE_ENTITY($exception->getMessage());
                break;

            case (InvalidBookingUUIDException::class):
                $response = OctoResponse::INVALID_BOOKING_UUID($exception->bookingUuid);
                break;

            case (InvalidRateIdException::class):
                $response = OctoResponse::INVALID_RATE_ID($exception->getRateId());
                break;

            case (NoMatchingDataException::class):
                $response = OctoResponse::INVALID_PRODUCT_ID($exception->productId);
                break;
                
            case (BadRequestException::class):
                $response = OctoResponse::BAD_REQUEST($exception->getMessage());
                break;

            case (SupplierSubsystemError::class):
                $response = OctoResponse::SUBSYSTEM_ERROR($exception->getErrorMessage());
                break;
            
            case (TooManyDeparturesException::class):
                $response = OctoResponse::TOO_MANY_DEPARTURES();
                break;

            case (APIThrottleError::class):
                JSONLog::error(["message" => "TourCMS API Rate Limit reached, returning 429 error", "exception" => $exception]);
                $response = OctoResponse::TOO_MANY_REQUEST();
                break;
            
            case (APICallNotOKException::class):

                try {
                    JSONLog::error(["message" => "TourCMS API Call error"]);
                } catch (BindingResolutionException) {
                    parent::report($exception);
                }
                
                $response = OctoResponse::INTERNAL_SERVER_ERROR();
                break;

            case (NoAPIResponseException::class):

                try {
                    JSONLog::error(["message" => "TourCMS API Call error, no response"]);
                } catch (BindingResolutionException) {
                    parent::report($exception);
                }
                
                $response = OctoResponse::INTERNAL_SERVER_ERROR();
                break;

            default:
                try  {
                    JSONLog::error(["message" => "Exception captured by custom error handler", "exception" => $exception]);
                    return OctoResponse::INTERNAL_SERVER_ERROR();
                } catch (BindingResolutionException) {
                    parent::report($exception);
                    return new JsonResponse([], Response::HTTP_INTERNAL_SERVER_ERROR);
                }   
        }

        try {
            JSONLog::info(["message" => "Exception captured by custom exception handler, returning response", "response" => json_decode($response->getContent())]);
        } catch (BindingResolutionException) {}

        return $response;
    }
}