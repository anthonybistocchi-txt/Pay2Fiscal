<?php

namespace App\Repositories\Purchase\DTO;

/**
 * Payload for persisting a new transaction (repository boundary).
 */
final class CreateTransactionInput
{
    public function __construct(
        public readonly int     $userId,
        public readonly int     $productId,
        public readonly int     $paymentAmount,
        public readonly string  $paymentMethod,
        public readonly string  $paymentStatus,
        public readonly string  $idempotencyKey,
        public readonly string  $transactionUuid,
        public readonly ?int    $gatewayId,
        public readonly ?string $last4DigitsCardNumber,
        public readonly ?string $cardBrand,
        public readonly int     $quantity,
    ) {}
}
