<?php

namespace App\Http\Middleware;

use App\Http\Responses\OctoResponse;
use App\Services\TourCMSService;
use Closure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AgentWithFullBookingPermissions
{
    public const ERROR_MESSAGE_NOT_FULL_BOOKING_PERMISSIONS = 'Your agent credentials does not allow you to create bookings';

    public function __construct(protected TourCMSService $tourCMSService) {}

    public function handle(Request $request, Closure $next): Response
    {

        if (! $this->agentHasElevatedPermissions()) {
            return OctoResponse::FORBIDDEN(self::ERROR_MESSAGE_NOT_FULL_BOOKING_PERMISSIONS);
        }

        return $next($request);
    }

    protected function agentHasElevatedPermissions(): bool
    {
        $showChannel = $this->tourCMSService->showChannel(cached: false);

        return (int) $showChannel->channel->connection_permission >= 3;
    }
}
