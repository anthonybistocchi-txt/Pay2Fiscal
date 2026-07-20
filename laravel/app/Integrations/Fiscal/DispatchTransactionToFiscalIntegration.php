<?php

namespace App\Integrations\Fiscal;

use App\Enums\FiscalStatus;
use App\Integrations\Fiscal\Contracts\DispatchTransactionToFiscalIntegrationInterface;
use App\Models\Emitter;
use App\Models\Transaction;
use App\Repositories\TransactionFiscalData\Contacts\TransactionFiscalDataRepositoryInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

final class DispatchTransactionToFiscalIntegration implements DispatchTransactionToFiscalIntegrationInterface
{
    /**
     * User-safe failure reason stored on the fiscal data row so consumers
     * can see why the fiscal document was not emitted, without internals.
     */
    private const REASON_FISCAL_REJECTED = 'Fiscal document was rejected by the fiscal service. Please verify the fiscal data.';
    private const TIMEOUT = 50;

    public function __construct(
        private readonly TransactionFiscalDataRepositoryInterface $fiscalDataRepository,
    ) {}

    public function dispatch(Transaction $transaction): void
    {
        Log::info('[Fluxo Pagamento] DispatchTransactionToFiscalIntegration::dispatch iniciado', [
            'payment_flow'      => true,
            'transaction_phase' => 'fiscal_integration',
            'transaction_id'    => $transaction->id,
            'transaction_uuid'  => $transaction->transaction_uuid,
            'idempotency_key'   => $transaction->idempotency_key,
        ]);

        $baseUrl      = (string) config('services.fiscal_api.base_url');
        $dispatchPath = (string) config('services.fiscal_api.dispatch_path');

        if ($baseUrl === '') 
        {
            Log::info('[Fluxo Pagamento] Envio fiscal ignorado: base_url não configurada', [
                'transaction_id' => $transaction->id,
            ]);

            return;
        }

        $fiscalData = $transaction->fiscalData;

        if ($fiscalData === null) 
        {
            Log::error('Fiscal dispatch aborted: missing fiscal data for transaction', [
                'transaction_id'   => $transaction->id,
                'transaction_uuid' => $transaction->transaction_uuid,
            ]);

            throw new RuntimeException(sprintf(
                'Transaction %d has no fiscal data to dispatch.',
                $transaction->id,
            ));
        }

        if (
            $fiscalData->fiscal_status !== FiscalStatus::PENDING 
            && 
            $fiscalData->fiscal_status !== FiscalStatus::PROCESSING) 
        {
            Log::info('[Fluxo Pagamento] Envio fiscal não necessário: status fiscal já finalizado ou em outro estado', [
                'fiscal_status' => $fiscalData->fiscal_status->value,
            ]);

            return;
        }

        if ($fiscalData->fiscal_status === FiscalStatus::PENDING) 
        {
            Log::info('[Fluxo Pagamento] Marcando dados fiscais como PROCESSING');
            $this->fiscalDataRepository->markAsProcessing($fiscalData);
        }

        $dispatchUrl = rtrim($baseUrl, '/').'/'.ltrim($dispatchPath, '/');

        Log::info('[Fluxo Pagamento] POST para API fiscal', [
            'dispatch_url' => $dispatchUrl,
        ]);

        try 
        {
            $response = Http::connectTimeout(self::TIMEOUT)
                ->timeout(self::TIMEOUT)
                ->withHeaders(['Idempotency-Key' => $transaction->idempotency_key])
                ->acceptJson()
                ->asJson()
                ->post($dispatchUrl, $this->buildRequestPayload($transaction));
        } 
        catch (Throwable $exception) 
        {
            Log::warning('Failed to reach fiscal service', [
                'transaction_id'   => $transaction->id,
                'transaction_uuid' => $transaction->transaction_uuid,
                'error_message'    => $exception->getMessage(),
                'error_class'      => $exception::class,
            ]);

            throw $exception;
        }

        if ($response->successful() && $response->json('status') === 'EMITTED') 
        {
            $this->fiscalDataRepository->markAsEmitted(
                $fiscalData,
                $response->json('request_id'),
            );

            Log::info('Fiscal document emitted successfully', [
                'transaction_id'    => $transaction->id,
                'transaction_uuid'  => $transaction->transaction_uuid,
                'fiscal_request_id' => $response->json('request_id'),
            ]);

            return;
        }

        if ($response->status() === 202 && $response->json('status') === 'PROCESSING') {
            $this->fiscalDataRepository->markAsProcessing($fiscalData);

            Log::info('Fiscal document still processing at tax authority', [
                'transaction_id'    => $transaction->id,
                'transaction_uuid'  => $transaction->transaction_uuid,
                'fiscal_request_id' => $response->json('request_id'),
            ]);

            return;
        }

        if ($response->json('status') === 'REJECTED') {
            $this->fiscalDataRepository->markAsRejected($fiscalData, [
                'error_message'     => $response->json('failure_reason'),
                'error_code'        => $response->status(),
                'fiscal_request_id' => $response->json('request_id'),
            ]);

            Log::warning('Fiscal document rejected', [
                'transaction_id'    => $transaction->id,
                'transaction_uuid'  => $transaction->transaction_uuid,
                'fiscal_request_id' => $response->json('request_id'),
                'fiscal_reason'     => $response->json('failure_reason'),
            ]);

            return;
        }

        if ($response->json('status') === 'DENIED') {
            $this->fiscalDataRepository->markAsDenied($fiscalData, [
                'error_message'     => $response->json('failure_reason'),
                'error_code'        => $response->status(),
                'fiscal_request_id' => $response->json('request_id'),
            ]);

            Log::warning('Fiscal document denied', [
                'transaction_id'    => $transaction->id,
                'transaction_uuid'  => $transaction->transaction_uuid,
                'fiscal_request_id' => $response->json('request_id'),
                'fiscal_reason'     => $response->json('failure_reason'),
            ]);

            return;
        }

        if ($response->json('status') === 'ERROR') {
            $this->fiscalDataRepository->markAsError($fiscalData, [
                'error_message'     => $response->json('failure_reason'),
                'error_code'        => $response->status(),
                'fiscal_request_id' => $response->json('request_id'),
            ]);

            Log::error('Fiscal document error', [
                'transaction_id'    => $transaction->id,
                'transaction_uuid'  => $transaction->transaction_uuid,
                'fiscal_request_id' => $response->json('request_id'),
                'fiscal_reason'     => $response->json('failure_reason'),
            ]);

            return;
        }

        throw new RuntimeException(sprintf(
            'Fiscal service rejected transaction %d (status %d).',
            $transaction->id,
            $response->status(),
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRequestPayload(Transaction $transaction): array
    {
        $emitter = Emitter::query()->first();

        return [
            'transaction_uuid'   => $transaction->transaction_uuid,
            'idempotency_key'    => $transaction->idempotency_key,
            'user_id'            => $transaction->user_id,
            'product_id'         => $transaction->product_id,
            'quantity'           => $transaction->quantity,
            'payment_amount'     => $transaction->payment_amount,
            'payment_method'     => $transaction->payment_method,
            'payment_status'     => $transaction->payment_status->value,
            'card_brand'         => $transaction->card_brand,
            'last_4_digits_card' => $transaction->last_4_digits_card_number,

            'transaction_fiscal_data' => $transaction->fiscalData === null ? null : [
                'origin_product'      => $transaction->fiscalData->origin_product,
                'ncm'                 => $transaction->fiscalData->ncm,
                'cfop'                => $transaction->fiscalData->cfop,
                'cest'                => $transaction->fiscalData->cest,
                'icms_cst_csosn'      => $transaction->fiscalData->icms_cst_csosn,
                'pis_cst'             => $transaction->fiscalData->pis_cst,
                'cofins_cst'          => $transaction->fiscalData->cofins_cst,
            ],
            'emitter' => $emitter === null ? null : [
                'legal_name'  => $emitter->legal_name,
                'trade_name'  => $emitter->trade_name,
                'cnpj'        => $emitter->cnpj,
                'ie'          => $emitter->ie,
                'im'          => $emitter->im,
                'tax_regime'  => $emitter->tax_regime,
                'crt'         => $emitter->crt,
                'address'     => [
                    'street'       => $emitter->street,
                    'number'       => $emitter->number,
                    'complement'   => $emitter->complement,
                    'neighborhood' => $emitter->neighborhood,
                    'city'         => $emitter->city,
                    'state'        => $emitter->state,
                    'zip_code'     => $emitter->zip_code,
                    'country'      => $emitter->country,
                ],
                'email' => $emitter->email,
                'phone' => $emitter->phone,
            ],
        ];
    }
}
