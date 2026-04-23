<?php

namespace App\DTOs\Purchase;

use App\Models\User;
use Carbon\Carbon;

/**
 * Immutable response for the transaction created use case.
 */
final class TransactionCreated
{
    public function __construct(
        public readonly string  $idempotencyKey,
        public readonly string  $transactionId,
        public readonly ?Carbon $paymentDate,
        public readonly ?int    $gatewayId,
        public readonly ?string $last4DigitsCardNumber,
        public readonly int     $paymentAmount,
        public readonly string  $paymentMethod,
        public readonly ?string $cardBrand,
        public readonly string  $status,
        public readonly int     $quantity,
        public readonly User    $user,
        public readonly int     $productId,
    ) {}
}
