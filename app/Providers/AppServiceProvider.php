<?php

namespace App\Providers;

use App\Payments\Contracts\PaymentGateway;
use App\Payments\Gateways\DokuCheckoutGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, DokuCheckoutGateway::class);
    }

    public function boot(): void
    {
        RateLimiter::for('reports', function (Request $request): Limit {
            return Limit::perHour(10)->by((string) $request->user()?->id);
        });

        RateLimiter::for('registration', function (Request $request): Limit {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}
