<?php

namespace App\Http\Middleware;

use Symfony\Component\HttpFoundation\Response;
use App\Facades\OctoRequestFacade;
use App\Http\Requests\OctoRequest;
use Closure;
use Illuminate\Http\Request;

class CapabilitiesHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $capabilitiesStr = OctoRequestFacade::getActiveCapabilitiesAsString();
        $response->headers->set(OctoRequest::CAPABILITIES_HEADER, $capabilitiesStr);

        return $response;
    }
}
