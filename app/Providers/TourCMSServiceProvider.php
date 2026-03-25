<?php

namespace App\Providers;

use App\Http\Middleware\OctoAuthentication;
use App\Services\JSONLogService;
use App\Services\TourCMSService;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpFoundation\Request;

class TourCMSServiceProvider extends ServiceProvider
{   
    /**
     * Register any application services.
     */
    public function register(): void
    {

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->bind(TourCMSService::class, function ($app): TourCMSService {
            
            $request = app(Request::class);

            $maid = $request->get(OctoAuthentication::FIELD_MAID);
            $APIKey = $request->get(OctoAuthentication::FIELD_API_KEY);
            $channelId = $request->get(OctoAuthentication::FIELD_CHANNEL_ID);
    
            /** @var CacheFactory $cacheFactory */
            $cacheFactory = $app->make(CacheFactory::class);
            $redisCache = $cacheFactory->store('redis');

            $jsonLogService = $app->make(JSONLogService::class);

            return new TourCMSService((int) $maid, $APIKey, $channelId, $jsonLogService, $redisCache);
    
        });
    }
}
