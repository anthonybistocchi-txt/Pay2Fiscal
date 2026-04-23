<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Http\Resources\Purchase\TransactionCreatedResource;
use App\Services\Purchase\Contracts\PurchaseStoreServiceInterface;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TransactionController extends Controller
{
    public function __construct(
        private readonly PurchaseStoreServiceInterface $purchaseStoreService,
    ) {}

    public function purchase(PurchaseRequest $request): JsonResponse
    {
        $transactionCreated = $this->purchaseStoreService->handle($request->toDto());

        return TransactionCreatedResource::make($transactionCreated)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
