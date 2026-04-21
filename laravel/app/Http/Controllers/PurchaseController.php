<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseStoreRequest;

class PurchaseController extends Controller
{

    public function store(PurchaseStoreRequest $request)
    {
        return 'purchase is processing';
    }
}
