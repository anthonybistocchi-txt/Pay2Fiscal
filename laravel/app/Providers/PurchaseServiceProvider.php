<?php

namespace App\Providers;

use App\Integrations\Fiscal\Contracts\DispatchTransactionToFiscalServiceInterface;
use App\Integrations\Fiscal\DispatchTransactionToFiscalService;
use App\Integrations\Gateway\Contracts\DispatchPaymentGatewayServiceInterface;
use App\Integrations\Gateway\DispatchPaymentGateways;
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
        DispatchTransactionToFiscalServiceInterface::class  => DispatchTransactionToFiscalService::class,
        DispatchPaymentGatewayServiceInterface::class       => DispatchPaymentGateways::class,
    ];

    public function register(): void
    {
        foreach (self::BINDINGS as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }
    }
}
