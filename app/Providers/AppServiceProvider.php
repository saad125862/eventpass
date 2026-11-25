<?php

namespace App\Providers;

use App\Services\Payments\MockGateway;
use App\Services\Payments\PaymentGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Swap this binding for a real provider (Stripe, SSLCommerz...) without touching callers.
        $this->app->bind(PaymentGateway::class, MockGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        RateLimiter::for('booking', fn (Request $request) => Limit::perMinute(20)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('checkin', fn (Request $request) => Limit::perMinute(180)->by($request->user()?->id ?: $request->ip()));
    }
}
