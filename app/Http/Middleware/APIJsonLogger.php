<?php

namespace App\Http\Middleware;

use App\Models\APIJsonLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;

/**
 * Middleware used to fill APIJsonModel
 */
class APIJsonLogger
{
    /**
     * This method fills APIJsonLog data before and after the request is processed
     * @return void
     */
    public function handle(Request $request, Closure $next): JsonResponse
    {
        $apiJsonLog = new APIJsonLog();

        // Before the request is processed
        $this->fillBeforeResponse($request, $apiJsonLog);

        // We save the log in the request in order to be accesible in controllers to fill specific data
        $request->attributes->set('apiJsonLog', $apiJsonLog);

        // We let others middlewares/controller handle the request
        try {
            $response = $next($request);
        } catch (Throwable $e) {
            $this->fillAfterException($apiJsonLog, $e);
        }

        // After the request is processed
        $this->fillAfterResponse($apiJsonLog, $response);

        $this->writeLog($apiJsonLog);

        return $response;
    }

    /* PROTECTED METHODS */
    protected function fillBeforeResponse(Request $request, APIJsonLog $log): APIJsonLog
    {
        $log->timestamp = microtime(true);
        $log->message          = '';
        $log->xRequestId       = $request->headers->get(OctoAuthentication::FIELD_X_REQUEST_ID);
        $log->xCorrelationId   = $request->headers->get(OctoAuthentication::FIELD_X_CORRELATION_ID);
        $log->userAgent        = $request->userAgent() ?? '';
        $log->ipAddress        = $request->ip() ?? '';
        $log->url              = $request->path();
        $log->verb             = $request->method();
        $log->requestHeaders   = $request->headers->all();
        $log->requestBody      = $this->getRequestBody($request);
        $log->queryString      = $request->getQueryString() ?? '';
        $log->username         = optional($request->user())->name ?? null;
        return $log;
    }

    protected function fillAfterResponse(APIJsonLog $apiJsonLog, JsonResponse $response): void
    {
        $executionTimeMs = (int) ((microtime(true) - $apiJsonLog->timestamp) * 1000);

        $apiJsonLog->capabilities    = $response->headers->get('Octo-Capabilities', ""); 
        $apiJsonLog->executionTime   = $executionTimeMs;
        $apiJsonLog->responseBody    = $this->getResponseBody($response);
        $apiJsonLog->responseHeaders = $response->headers->all();
        $apiJsonLog->success         = $response->isSuccessful() ? '1' : '0';
        $apiJsonLog->errorLog        = !$response->isSuccessful();
        $apiJsonLog->error           = $response->isSuccessful() ? '' : 'HTTP ' . $response->getStatusCode();
        $apiJsonLog->timestamp       = time();
    }

    protected function fillAfterException(APIJsonLog $apiJsonLog, Throwable $e): void
    {
        $executionTimeMs = (int) ((microtime(true) - $apiJsonLog->timestamp) * 1000);

        $apiJsonLog->executionTime = $executionTimeMs;
        $apiJsonLog->success       = 0;
        $apiJsonLog->errorLog      = true;
        $apiJsonLog->error         = $e->getMessage();
        $apiJsonLog->exception     = (object) [
            'message' => $e->getMessage(),
            'code'    => $e->getCode(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
        ];
        $apiJsonLog->timestamp     = time();
    }

    protected function writeLog(APIJsonLog $APIJsonLog): void
    {
        $encodedJSONLog = json_encode($APIJsonLog, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        Log::info($encodedJSONLog);
        // TODO replace when ready
        //$APIJsonLogFile = env('API_JSON_LOG_FILE');
        //error_log($encodedJSONLog, 3, $APIJsonLogFile);
        //error_log(PHP_EOL, 3, $APIJsonLogFile);
    }

    private function getRequestBody(Request $request): mixed
    {
        if (in_array(strtoupper($request->method()), ['POST', 'PUT', 'PATCH'])) {
            $content = $request->getContent();
            if ($content === '' || $content === null) {
                return null;
            }

            $json = json_decode($content, true);
            return $json !== null ? $json : $content;
        }

        return null;
    }

    private function getResponseBody(JsonResponse $response): mixed
    {
        $content = $response->getContent();

        if ($content === '' || $content === null) {
            return null;
        }

        return $content;
    }
}