<?php

declare(strict_types=1);

namespace App\Exceptions\Purchase;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Thrown by the purchase use case when the requested quantity exceeds the
 * available stock for the product. Returns a user-safe message that does
 * not leak the exact remaining quantity (avoids stock probing).
 */
class InsufficientStockException extends Exception
{
    public function __construct(
        public readonly int $productId,
        public readonly int $requested,
        public readonly int $available,
        string $message = 'The requested quantity is not available for this product.',
    ) {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json(
            ['message' => $this->getMessage()],
            Response::HTTP_CONFLICT,
        );
    }
}
