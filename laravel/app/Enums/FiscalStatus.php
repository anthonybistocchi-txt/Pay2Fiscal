<?php

namespace App\Enums;

/**
 * Lifecycle of the fiscal document (NF-e) emission.
 *
 * PENDING    -> awaiting dispatch to the fiscal service.
 * PROCESSING -> a worker is currently dispatching to the fiscal service.
 * EMITTED    -> the fiscal document was successfully emitted.
 * REJECTED   -> the fiscal document was rejected by the tax authority / fiscal service.
 * ERROR      -> a system failure prevented the fiscal document from being emitted.
 * CANCELED   -> the fiscal document was canceled after emission.
 */
enum FiscalStatus: string
{
    case PENDING    = 'PENDING';
    case PROCESSING = 'PROCESSING';
    case EMITTED    = 'EMITTED';
    case REJECTED   = 'REJECTED';
    case ERROR      = 'ERROR';
    case CANCELED   = 'CANCELED';
    case DENIED     = 'DENIED';
    /**
     * Human readable, user-safe message describing the current fiscal state.
     */
    public function userMessage(): string
    {
        return match ($this) {
            self::PENDING    => 'Fiscal document is awaiting emission.',
            self::PROCESSING => 'Fiscal document is being emitted.',
            self::EMITTED    => 'Fiscal document was emitted successfully.',
            self::REJECTED   => 'Fiscal document was rejected. Please verify the fiscal data and try again.',
            self::ERROR      => 'Fiscal document could not be emitted at this time. A new attempt will be made shortly.',
            self::CANCELED   => 'Fiscal document was canceled.',
            self::DENIED     => 'Fiscal document was denied due to tax irregularity. The invoice number cannot be reused.',
        };
    }

    public function isFinal(): bool
    {
        return match ($this) {
            self::EMITTED, self::REJECTED, self::ERROR, self::CANCELED, self::DENIED => true,
            default                                                    => false,
        };
    }
}
