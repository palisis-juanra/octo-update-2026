<?php

namespace App\Http\Middleware;

use App\Http\Responses\OctoResponse;
use App\Services\TourCMSService;
use Closure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AgentWithPermissions
{
    public const ERROR_MESSAGE_NO_ELEVATED_PERMISSIONS = "Your credentials does not give you access to bookings";

    public function __construct(protected TourCMSService $tourCMSService) {}

    public function handle(Request $request, Closure $next): Response
    {
    
        if (!$this->agentHasElevatedPermissions()) {
            return OctoResponse::FORBIDDEN(self::ERROR_MESSAGE_NO_ELEVATED_PERMISSIONS);
        }

        return $next($request);
    }

    protected function agentHasElevatedPermissions(): bool
    {
        $showChannel = $this->tourCMSService->showChannel(cached: false);

        return (int) $showChannel->channel->connection_permission >= 3; 
    }
}