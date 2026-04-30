<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimiters();
    }

    /**
     * Application-level rate limiters. The "purchases" limiter is applied
     * to the financial endpoint to mitigate accidental flooding (a leaked
     * token spamming /purchase) and brute-force-style discovery.
     *
     * Two layers are stacked:
     *   - per authenticated user (primary defence);
     *   - per client IP (fallback that also covers unauthenticated probes).
     */
    private function configureRateLimiters(): void
    {
        RateLimiter::for('purchases', static function (Request $request): array {
            $userId = $request->user()?->getAuthIdentifier();

            return [
                Limit::perMinute(10)->by('user:'.($userId ?? 'guest')),
                Limit::perMinute(60)->by('ip:'.$request->ip()),
            ];
        });
    }
}
