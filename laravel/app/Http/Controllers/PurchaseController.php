<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Http\Resources\Transaction\TransactionResource;
use App\Services\Purchase\Contracts\PurchaseStoreServiceInterface;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PurchaseController extends Controller
{
    public function __construct(
        private readonly PurchaseStoreServiceInterface $purchaseStoreService,
    ){}

    public function store(PurchaseRequest $request): JsonResponse
    {
        $transactionCreated = $this->purchaseStoreService->storePurchase($request->toDto());

        return TransactionResource::make($transactionCreated)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
