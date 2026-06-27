<?php

namespace App\Enums;

/**
 * Lifecycle of the payment / gateway dispatch.
 *
 * PENDING    -> awaiting dispatch to a payment gateway.
 * PROCESSING -> a worker is currently dispatching to a gateway.
 * APPROVED   -> a gateway accepted the payment.
 * REJECTED   -> the payment was declined by a gateway business rule.
 * ERROR      -> a system failure prevented the payment from being processed.
 * REFUNDED   -> the payment was refunded after being approved.
 */
enum PaymentStatus: string
{
    case PENDING    = 'PENDING';
    case PROCESSING = 'PROCESSING';
    case APPROVED   = 'APPROVED';
    case REJECTED   = 'REJECTED';
    case ERROR      = 'ERROR';
    case REFUNDED   = 'REFUNDED';

    /**
     * Human readable, user-safe message describing the current payment state.
     */
    public function userMessage(): string
    {
        return match ($this) {
            self::PENDING    => 'Payment is awaiting processing.',
            self::PROCESSING => 'Payment is being processed.',
            self::APPROVED   => 'Payment was approved.',
            self::REJECTED   => 'Payment was declined. Please verify the payment details or try another method.',
            self::ERROR      => 'Payment could not be processed at this time. Please try again shortly.',
            self::REFUNDED   => 'Payment was refunded.',
        };
    }

    public function isFinal(): bool
    {
        return match ($this) {
            self::APPROVED, self::REJECTED, self::ERROR, self::REFUNDED => true,
            default                                                     => false,
        };
    }
}
