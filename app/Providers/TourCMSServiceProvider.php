<?php

namespace App\Providers;

use App\Http\Middleware\OctoAuthentication;
use App\Services\TourCMSService;
use Illuminate\Support\Facades\Log;
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
        $this->app->bind(TourCMSService::class, function (): TourCMSService {
            
            $request = app(Request::class);

            $maid = $request->get(OctoAuthentication::FIELD_MAID);
            $APIKey = $request->get(OctoAuthentication::FIELD_API_KEY);
    
            return new TourCMSService($maid, $APIKey);
    
        });
    }
}
