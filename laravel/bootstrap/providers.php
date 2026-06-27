<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\PurchaseServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    PurchaseServiceProvider::class,
    EventServiceProvider::class,
];
