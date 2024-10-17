<?php

namespace App\Http\Middleware;

use App\Http\Responses\OctoResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OctoAuthentication
{
    // MAID|CHANNEL_ID|API_KEY
    const AUTH_PATTERN_REGEX = '/^\d+\|\d+\|.+/';
    const AUTH_SEPARATOR = '|';

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

        $request->merge([
            'maid' => $this->getMaidFromAuthHeader($auth),
            'channel' => $this->getChannelFromAuthHeader($auth),
            'APIKey' => $this->getAPIKeyFromAuthHeader($auth)
        ]);
         
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
}
