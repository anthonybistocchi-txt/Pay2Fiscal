<?php

namespace App\Providers;

use App\Repositories\Auth\Contract\RegisterUserRepositoryInterface;
use App\Repositories\Auth\RegisterUserRepository;
use App\Repositories\Auth\LoginUserRepository;
use App\Repositories\Auth\Contract\LoginUserRepositoryInterface;
use App\Services\Auth\Contracts\LoginServiceInterface;
use App\Services\Auth\Contracts\LogoutServiceInterface;
use App\Services\Auth\Contracts\RegisterServiceInterface;
use App\Services\Auth\LoginService;
use App\Services\Auth\LogoutService;
use App\Services\Auth\RegisterService;
use Illuminate\Support\ServiceProvider;

/**
 * Binds the authentication use-case interfaces to their default implementations.
 *
 * Swap an implementation here (e.g. to use a different token issuer or data source)
 * without touching the controller or the HTTP layer.
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    private const BINDINGS = [
        LoginServiceInterface::class           => LoginService::class,
        RegisterServiceInterface::class        => RegisterService::class,
        LogoutServiceInterface::class          => LogoutService::class,
        RegisterUserRepositoryInterface::class => RegisterUserRepository::class,
        LoginUserRepositoryInterface::class    => LoginUserRepository::class,
    ];

    public function register(): void
    {
        foreach (self::BINDINGS as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }
    }
}
