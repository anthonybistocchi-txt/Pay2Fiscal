<?php

namespace App\Integrations\Gateway;

use App\Enums\PaymentStatus;
use App\Events\TransactionApproved;
use App\Integrations\Gateway\Contracts\DispatchPaymentGatewayServiceInterface;
use App\Models\Gateway;
use App\Models\Transaction;
use App\Repositories\Transaction\Contracts\TransactionRepositoryInterface;
use App\Repositories\TransactionFiscalData\Contacts\TransactionFiscalDataRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

final class DispatchPaymentGateways implements DispatchPaymentGatewayServiceInterface
{
    /**
     * Time-to-first-byte budget for the gateway. Anything beyond this is
     * considered hung and the request is aborted to free the worker. The
     * job retry loop is responsible for the next attempt.
     */
    private const HTTP_TIMEOUT = 20;

    /**
     * TCP connect budget. A fast fail here lets us move to the next gateway
     * by priority instead of holding the connection.
     */
    private const HTTP_CONNECT_TIMEOUT = 5;

    /**
     * User-safe failure reasons stored on the transaction so consumers can
     * understand why the payment did not succeed without exposing internals.
     */
    private const REASON_NO_GATEWAYS_AVAILABLE = 'Payment service is temporarily unavailable. Please try again shortly.';
    private const REASON_ALL_GATEWAYS_REJECTED = 'Payment was declined by every available provider. Please verify the payment details or try another method.';

    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly TransactionFiscalDataRepositoryInterface $transactionFiscalDataRepository,
    ) {}

    public function dispatch(Transaction $transaction): void
    {
        Log::info('[Fluxo Pagamento] DispatchPaymentGateways::dispatch iniciado', [
            'transaction_phase' => 'gateway_integration',
            'transaction_id'    => $transaction->id,
            'transaction_uuid'  => $transaction->transaction_uuid,
            'idempotency_key'   => $transaction->idempotency_key,
            'payment_status'    => $transaction->payment_status->value,
            'payment_amount'    => $transaction->payment_amount,
            'payment_method'    => $transaction->payment_method,
        ]);

        if ($transaction->payment_status !== PaymentStatus::PROCESSING) 
        {
            Log::warning('[Fluxo Pagamento] Integração gateway abortada: status diferente de PROCESSING', [
                'payment_status' => $transaction->payment_status->value,
            ]);

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

        Log::info('[Fluxo Pagamento] Gateways ativos carregados por prioridade', [
            'gateway_count' => $gateways->count(),
            'gateway_order' => $gateways->pluck('name')->all(),
        ]);

        if ($gateways->isEmpty()) 
        {
            $this->transactionRepository->markAsError($transaction, [
                'error_message' => self::REASON_NO_GATEWAYS_AVAILABLE,
            ]);

            $this->cancelFiscalForFailedPayment($transaction, self::REASON_NO_GATEWAYS_AVAILABLE);

            Log::error('No active payment gateways available', [
                'transaction_id'   => $transaction->id,
                'transaction_uuid' => $transaction->transaction_uuid,
            ]);

            throw new RuntimeException('No active payment gateways available.');
        }

        foreach ($gateways as $gateway) 
        {
            Log::info('[Fluxo Pagamento] Tentando gateway na ordem de prioridade', [
                'gateway_id'   => $gateway->id,
                'gateway_name' => $gateway->name,
            ]);

            if ($this->tryDispatchToGateway($transaction, $gateway)) 
            {
                Log::info('[Fluxo Pagamento] Fluxo de gateway encerrado com sucesso para este gateway', [
                    'gateway_id'   => $gateway->id,
                    'gateway_name' => $gateway->name,
                ]);

                return;
            }
        }

        $this->transactionRepository->markAsRejected($transaction, [
            'error_message' => self::REASON_ALL_GATEWAYS_REJECTED,
        ]);

        $this->cancelFiscalForFailedPayment($transaction, self::REASON_ALL_GATEWAYS_REJECTED);

        Log::warning('All payment gateways rejected the transaction', [
            'transaction_id'   => $transaction->id,
            'transaction_uuid' => $transaction->transaction_uuid,
            'gateways_tried'   => $gateways->pluck('name')->all(),
        ]);
    }

    private function tryDispatchToGateway(Transaction $transaction, Gateway $gateway): bool
    {
        $url = $this->buildGatewayUrl($gateway);

        Log::info('[Fluxo Pagamento] POST para gateway de pagamento', [
            'gateway_id'   => $gateway->id,
            'dispatch_url' => $url,
        ]);

        try 
        {
            $response = Http::connectTimeout(self::HTTP_CONNECT_TIMEOUT)
                ->timeout(self::HTTP_TIMEOUT)
                ->withHeaders(['Idempotency-Key' => $transaction->idempotency_key])
                ->acceptJson()
                ->asJson()
                ->post($url, $this->buildRequestPayload($transaction));

            Log::info('[Fluxo Pagamento] Resposta HTTP recebida do gateway', [
                'gateway_id'      => $gateway->id,
                'response_status' => $response->status(),
                'successful'      => $response->successful(),
            ]);
        } 
        catch (Throwable $exception) 
        {
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

        if ($response->failed()) 
        {
            Log::warning('Payment gateway dispatch failed', [
                'reason'            => 'http_failed',
                'transaction_id'    => $transaction->id,
                'transaction_uuid'  => $transaction->transaction_uuid,
                'gateway_id'        => $gateway->id,
                'gateway_name'      => $gateway->name,
                'response_status'   => $response->status(),
                'response_body'     => $response->body(),
            ]);

            return false;
        }
        
        $idempotencyKey = $response->json('idempotency_key');
        
        if (!is_string($idempotencyKey) || $idempotencyKey === '') 
        {
            Log::warning('Payment gateway returned invalid payload', [
                'reason'            => 'missing_or_invalid_idempotency_key',
                'transaction_id'    => $transaction->id,
                'transaction_uuid'  => $transaction->transaction_uuid,
                'gateway_id'        => $gateway->id,
                'gateway_name'      => $gateway->name,
                'response_status'   => $response->status(),
                'response_body'     => $response->body(),
                'received_value'    => $idempotencyKey, // pode ser null, array, etc.
                'received_type'     => gettype($idempotencyKey),
            ]);

            return false;
        }
        
        if ($idempotencyKey !== $transaction->idempotency_key) 
        {
            Log::error('Payment gateway returned mismatching idempotency key', [
                'reason'                   => 'idempotency_key_mismatch',
                'transaction_id'           => $transaction->id,
                'transaction_uuid'         => $transaction->transaction_uuid,
                'gateway_id'               => $gateway->id,
                'gateway_name'             => $gateway->name,
                'expected_idempotency_key' => $transaction->idempotency_key,
                'received_idempotency_key' => $idempotencyKey,
            ]);

            return false;
        }

        $this->transactionRepository->markAsApproved($transaction, $gateway->id);

        Log::info('[Fluxo Pagamento] Transação marcada como APPROVED; disparando TransactionApproved para fiscal', [
            'gateway_id'   => $gateway->id,
            'gateway_name' => $gateway->name,
        ]);

        event(new TransactionApproved($transaction)); // Envia o evento para o listener EnqueueFiscalDispatch

        Log::info('Payment gateway dispatched successfully', [
            'transaction_id'   => $transaction->id,
            'transaction_uuid' => $transaction->transaction_uuid,
            'gateway_id'       => $gateway->id,
            'gateway_name'     => $gateway->name,
        ]);

        return true;
    }

    private function cancelFiscalForFailedPayment(Transaction $transaction, string $reason): void
    {
        $transaction->loadMissing('fiscalData');

        $this->transactionFiscalDataRepository->cancelDueToPaymentFailure(
            $transaction->fiscalData,
            $reason,
        );
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
