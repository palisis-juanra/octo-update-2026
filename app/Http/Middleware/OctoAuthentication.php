<?php

namespace App\Http\Middleware;

use App\Http\Responses\OctoResponse;
use App\Providers\JSONLogServiceProvider;
use App\Providers\TourCMSServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;


class OctoAuthentication
{
    // MAID|CHANNEL_ID|API_KEY
    const AUTH_PATTERN_REGEX = '/^\d+\|\d+\|.+/';
    const AUTH_SEPARATOR = '|';
    const FIELD_API_KEY = 'APIKey';
    const FIELD_CHANNEL_ID = 'channel';
    const FIELD_X_CORRELATION_ID = 'X-Correlation-Id';
    const FIELD_X_REQUEST_ID = 'X-Request-Id';
    const FIELD_MAID = 'maid';

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $auth = $request->bearerToken();
    
        if (is_null($auth)) {
            return OctoResponse::UNAUTHORIZED();
        }

        if (preg_match(self::AUTH_PATTERN_REGEX, $auth) === 0) {
            return OctoResponse::FORBIDDEN();
        }

        $xCorrelationId = $this->getCorrelationIdFromHeaders($request) ?? Str::uuid();
        $xRequestId = $this->getRequestIdFromHeaders($request) ?? Str::uuid();

        $request->headers->set(self::FIELD_X_CORRELATION_ID, $xCorrelationId);
        $request->headers->set(self::FIELD_X_REQUEST_ID, $xRequestId);

        $request->merge([
            self::FIELD_MAID => $this->getMaidFromAuthHeader($auth),
            self::FIELD_CHANNEL_ID => $this->getChannelFromAuthHeader($auth),
            self::FIELD_API_KEY => $this->getAPIKeyFromAuthHeader($auth),
            self::FIELD_X_CORRELATION_ID => $xCorrelationId, 
            self::FIELD_X_REQUEST_ID => $xRequestId
        ]);


        // We should not register ServiceProviders in test enviroment
        if (env('APP_ENV') === 'testing') {
            return $next($request);
        }

        /* At this point we have the credentials for TourCMS, then
        we can register the service providers */
        app()->register(TourCMSServiceProvider::class);
        app()->register(JSONLogServiceProvider::class);

        return $next($request);
    }

    protected function getMaidFromAuthHeader(string $auth): string
    {
        return explode(self::AUTH_SEPARATOR, $auth, 3)[0];
    }

    protected function getChannelFromAuthHeader($auth): string
    {
        return explode(self::AUTH_SEPARATOR, $auth, 3)[1];
    }

    protected function getAPIKeyFromAuthHeader(string $auth): string
    {
        return explode(self::AUTH_SEPARATOR, $auth, 3)[2];
    }

    public static function getCorrelationIdFromHeaders(Request $request): string | null
    {
        
        return $request->header('X-Correlation-Id');
    }

    public static function getRequestIdFromHeaders(Request $request): string | null
    {
        return $request->header('X-Request-Id');
    }

}
