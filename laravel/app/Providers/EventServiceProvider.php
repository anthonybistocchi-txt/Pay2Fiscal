<?php

namespace App\Providers;

use App\Events\TransactionApproved;
use App\Listeners\EnqueueFiscalDispatch;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, list<class-string>>
     */
    protected $listen = [
        TransactionApproved::class => [
            EnqueueFiscalDispatch::class,
        ],
    ];
}

