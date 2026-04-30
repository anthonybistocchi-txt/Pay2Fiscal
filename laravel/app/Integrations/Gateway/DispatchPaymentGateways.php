<?php

namespace App\Integrations\Gateway;

use App\Enums\PaymentStatus;
use App\Integrations\Gateway\Contracts\DispatchPaymentGatewayServiceInterface;
use App\Models\Gateway;
use App\Models\Transaction;
use App\Repositories\Transaction\Contracts\TransactionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

final class DispatchPaymentGateways implements DispatchPaymentGatewayServiceInterface
{
    private const TIMEOUT = 120;

    /**
     * User-safe failure reasons stored on the transaction so consumers can
     * understand why the payment did not succeed without exposing internals.
     */
    private const REASON_NO_GATEWAYS_AVAILABLE = 'Payment service is temporarily unavailable. Please try again shortly.';
    private const REASON_ALL_GATEWAYS_REJECTED = 'Payment was declined by every available provider. Please verify the payment details or try another method.';

    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository,
    ) {}

    public function dispatch(Transaction $transaction): void
    {
        if ($transaction->payment_status !== PaymentStatus::PROCESSING) {
            throw new RuntimeException(sprintf(
                'Transaction %d is not ready to dispatch (current status: %s).',
                $transaction->id,
                $transaction->payment_status->value,
            ));
        }

        /** @var Collection<int, Gateway> $gateways */
        $gateways = Gateway::query()
            ->where('active', true)
            ->orderBy('priority', 'asc')
            ->get();

        if ($gateways->isEmpty()) {
            $this->transactionRepository->markAsError($transaction, [
                'error_message' => self::REASON_NO_GATEWAYS_AVAILABLE,
            ]);

            Log::error('No active payment gateways available', [
                'transaction_id'   => $transaction->id,
                'transaction_uuid' => $transaction->transaction_uuid,
            ]);

            throw new RuntimeException('No active payment gateways available.');
        }

        foreach ($gateways as $gateway) {
            if ($this->tryDispatchToGateway($transaction, $gateway)) {
                return;
            }
        }

        $this->transactionRepository->markAsRejected($transaction, [
            'error_message' => self::REASON_ALL_GATEWAYS_REJECTED,
        ]);

        Log::warning('All payment gateways rejected the transaction', [
            'transaction_id'   => $transaction->id,
            'transaction_uuid' => $transaction->transaction_uuid,
            'gateways_tried'   => $gateways->pluck('name')->all(),
        ]);
    }

    private function tryDispatchToGateway(Transaction $transaction, Gateway $gateway): bool
    {
        $url = $this->buildGatewayUrl($gateway);

        try {
            $response = Http::timeout(self::TIMEOUT)
                ->acceptJson()
                ->asJson()
                ->post($url, $this->buildRequestPayload($transaction));
        } catch (Throwable $exception) {
            Log::warning('Failed to reach payment gateway', [
                'transaction_id'   => $transaction->id,
                'transaction_uuid' => $transaction->transaction_uuid,
                'gateway_id'       => $gateway->id,
                'gateway_name'     => $gateway->name,
                'error_message'    => $exception->getMessage(),
                'error_class'      => $exception::class,
            ]);

            return false;
        }

        $payload = $response->json() ?? [];

        if ($response->failed() || !isset($payload['idempotency_key'])) {
            Log::warning('Payment gateway returned an invalid response', [
                'transaction_id'   => $transaction->id,
                'transaction_uuid' => $transaction->transaction_uuid,
                'gateway_id'       => $gateway->id,
                'gateway_name'     => $gateway->name,
                'response_status'  => $response->status(),
                'response_body'    => $response->body(),
            ]);

            return false;
        }

        if ($payload['idempotency_key'] !== $transaction->idempotency_key) {
            Log::error('Payment gateway returned a mismatching idempotency key', [
                'transaction_id'           => $transaction->id,
                'transaction_uuid'         => $transaction->transaction_uuid,
                'gateway_id'               => $gateway->id,
                'gateway_name'             => $gateway->name,
                'expected_idempotency_key' => $transaction->idempotency_key,
                'received_idempotency_key' => $payload['idempotency_key'],
            ]);

            return false;
        }

        $this->transactionRepository->markAsApproved($transaction, $gateway->id);

        Log::info('Payment gateway dispatched successfully', [
            'transaction_id'   => $transaction->id,
            'transaction_uuid' => $transaction->transaction_uuid,
            'gateway_id'       => $gateway->id,
            'gateway_name'     => $gateway->name,
        ]);

        return true;
    }

    private function buildGatewayUrl(Gateway $gateway): string
    {
        return rtrim($gateway->base_url, '/').'/'.ltrim($gateway->dispatch_path, '/');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRequestPayload(Transaction $transaction): array
    {
        return [
            'transaction_id'   => $transaction->id,
            'transaction_uuid' => $transaction->transaction_uuid,
            'payment_amount'   => $transaction->payment_amount,
            'payment_method'   => $transaction->payment_method,
            'payment_status'   => $transaction->payment_status->value,
            'payment_date'     => $transaction->payment_date,
            'idempotency_key'  => $transaction->idempotency_key,
        ];
    }
}
