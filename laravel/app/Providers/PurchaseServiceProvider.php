<?php

namespace App\Providers;

use App\Integrations\Fiscal\Contracts\DispatchTransactionToFiscalIntegrationInterface;
use App\Integrations\Fiscal\DispatchTransactionToFiscalIntegration;
use App\Integrations\Gateway\Contracts\DispatchPaymentGatewayIntegrationInterface;
use App\Integrations\Gateway\DispatchPaymentGatewayIntegration;
use App\Repositories\Transaction\Contracts\TransactionRepositoryInterface;
use App\Repositories\Transaction\TransactionRepository;
use App\Repositories\TransactionFiscalData\Contacts\TransactionFiscalDataRepositoryInterface;
use App\Repositories\TransactionFiscalData\TransactionFiscalDataRepository;
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
        PurchaseStoreServiceInterface::class                => PurchaseStoreService::class,
        TransactionRepositoryInterface::class               => TransactionRepository::class,
        TransactionFiscalDataRepositoryInterface::class     => TransactionFiscalDataRepository::class,
        DispatchTransactionToFiscalIntegrationInterface::class => DispatchTransactionToFiscalIntegration::class,
        DispatchPaymentGatewayIntegrationInterface::class      => DispatchPaymentGatewayIntegration::class,
    ];

    public function register(): void
    {
        foreach (self::BINDINGS as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }
    }
}
