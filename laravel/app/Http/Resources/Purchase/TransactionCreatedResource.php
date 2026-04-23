<?php

namespace App\Http\Resources\Purchase;

use App\DTOs\Purchase\TransactionCreated;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API representation of the TransactionCreated DTO after a successful purchase.
 */
class TransactionCreatedResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var TransactionCreated $transactionCreated */
        $transactionCreated = $this->resource;

        return [
            'idempotency_key'           => $transactionCreated->idempotencyKey,
            'transaction_uuid'          => $transactionCreated->transactionUuid,
            'payment_date'              => $transactionCreated->paymentDate?->format(DateTimeInterface::ATOM),
            'gateway_id'                => $transactionCreated->gatewayId,
            'last_4_digits_card_number' => $transactionCreated->last4DigitsCardNumber,
            'payment_amount'            => $transactionCreated->paymentAmount,
            'payment_method'            => $transactionCreated->paymentMethod,
            'card_brand'                => $transactionCreated->cardBrand,
            'payment_status'            => $transactionCreated->status,
            'quantity'                  => $transactionCreated->quantity,
            'product_id'                => $transactionCreated->productId,
            'user' => [
                'id' => $transactionCreated->user->id,
                'name' => $transactionCreated->user->name,
                'email' => $transactionCreated->user->email,
            ],
        ];
    }
}
