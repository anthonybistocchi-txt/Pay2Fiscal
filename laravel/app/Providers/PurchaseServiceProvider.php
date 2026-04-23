<?php

namespace App\Providers;

use App\Integrations\Go\Contracts\DispatchTransactionToFiscalServiceInterface;
use App\Integrations\Go\DispatchTransactionToFiscalService;
use App\Repositories\Transaction\Contracts\TransactionRepositoryInterface;
use App\Repositories\Transaction\TransactionRepository;
use App\Services\Purchase\Contracts\PurchaseStoreServiceInterface;
use App\Services\Purchase\PurchaseStoreService;
use Illuminate\Support\ServiceProvider;

/**
 * Binds purchase use-case interfaces and repositories to their default implementations.
 */
class PurchaseServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    private const BINDINGS = [
        PurchaseStoreServiceInterface::class => PurchaseStoreService::class,
        TransactionRepositoryInterface::class => TransactionRepository::class,
        DispatchTransactionToFiscalServiceInterface::class => DispatchTransactionToFiscalService::class,
    ];

    public function register(): void
    {
        foreach (self::BINDINGS as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }
    }
}
