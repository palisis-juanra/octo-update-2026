<?php

namespace App\Http\Middleware;

use App\Facades\OctoRequestFacade;
use App\Http\Requests\OctoRequest;
use Illuminate\Http\Request;
use Closure;

class CapabilitiesHeader
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $capabilitiesStr = OctoRequestFacade::getActiveCapabilitiesAsString();
        $response->headers->set(OctoRequest::CAPABILITIES_HEADER, $capabilitiesStr);

        return $response;
    }
}