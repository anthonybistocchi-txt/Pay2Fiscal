<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Http\Resources\Transaction\TransactionResource;
use App\Services\Purchase\Contracts\PurchaseStoreServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PurchaseController extends Controller
{
    public function __construct(
        private readonly PurchaseStoreServiceInterface $purchaseStoreService,
    ){}

    public function store(PurchaseRequest $request): JsonResponse
    {
        $dto = $request->toDto();

        Log::withContext([
            'payment_flow'      => true,
            'idempotency_key'   => $dto->idempotencyKey,
            'user_id'           => $dto->user->id,
            'product_id'        => $dto->productId,
            'transaction_phase' => 'http',
        ]);

        Log::info('[Fluxo Pagamento] Requisição POST /purchase recebida e validada', [
            'quantity'       => $dto->quantity,
            'payment_method' => $dto->paymentMethod,
        ]);

        $transactionCreated = $this->purchaseStoreService->storePurchase($dto);

        Log::info('[Fluxo Pagamento] Compra persistida; resposta 201 montada', [
            'transaction_uuid' => $transactionCreated->transactionUuid,
            'status'           => $transactionCreated->status,
        ]);

        return TransactionResource::make($transactionCreated)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
