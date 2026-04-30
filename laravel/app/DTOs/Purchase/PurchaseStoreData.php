<?php

namespace App\DTOs\Purchase;

use App\Models\User;

/**
 * Immutable payload for the purchase store use case.
 */
final class PurchaseStoreData
{
    public function __construct(
        public readonly int $quantity,
        public readonly User $user,
        public readonly int $productId,
        public readonly string $paymentMethod,
        public readonly ?string $last4DigitsCardNumber,
        public readonly ?string $cardBrand,
        public readonly string $idempotencyKey,
    ) {}
}
