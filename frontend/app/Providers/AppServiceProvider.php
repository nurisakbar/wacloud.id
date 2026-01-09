<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\RateLimiter;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set Carbon locale to Indonesian
        Carbon::setLocale('id');
        if(env('FORCE_HTTPS',false)) { 
            URL::forceScheme('https');
        }
        
        // Configure rate limiter for webhook delivery
        // This limits webhook delivery jobs to prevent server overload
        RateLimiter::for('webhook-delivery', function ($job) {
            // Allow max 100 webhook delivery jobs per minute globally
            // This prevents too many concurrent HTTP requests
            return \Illuminate\Support\Facades\Limit::perMinute(100)->by('webhook-delivery-global');
        });
    }
}
