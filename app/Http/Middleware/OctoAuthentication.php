<?php

namespace App\Http\Middleware;

use App\Http\Responses\OctoResponse;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OctoAuthentication
{
    // MAID|AID|API_KEY
    const AUTH_PATTERN_REGEX = '/^\d+\|\d+\|.+/';

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
         
        return $next($request);
    }
}
