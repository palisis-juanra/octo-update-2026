<?php

namespace App\Providers;

use App\Http\Middleware\OctoAuthentication;
use App\Services\JSONLogService;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class JSONLogServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(JSONLogService::class, function (): JSONLogService {

            $request = app(Request::class);

            $channelId = $request->get(OctoAuthentication::FIELD_CHANNEL_ID);
            $maid = $request->get(OctoAuthentication::FIELD_MAID);
            $endpoint = substr($request->getPathInfo(), 1);
            $xCorrelationId = $request->get(OctoAuthentication::FIELD_X_CORRELATION_ID);

            return new JSONLogService($channelId, $maid, $endpoint, $xCorrelationId);

        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {}
}
