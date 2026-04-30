<?php

namespace App\Integrations\Fiscal;

use App\Enums\FiscalStatus;
use App\Integrations\Fiscal\Contracts\DispatchTransactionToFiscalServiceInterface;
use App\Models\Emitter;
use App\Models\Transaction;
use App\Repositories\TransactionFiscalData\Contacts\TransactionFiscalDataRepositoryInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

final class DispatchTransactionToFiscalService implements DispatchTransactionToFiscalServiceInterface
{
    /**
     * User-safe failure reason stored on the fiscal data row so consumers
     * can see why the fiscal document was not emitted, without internals.
     */
    private const REASON_FISCAL_REJECTED = 'Fiscal document was rejected by the fiscal service. Please verify the fiscal data.';

    public function __construct(
        private readonly TransactionFiscalDataRepositoryInterface $fiscalDataRepository,
    ) {}

    public function dispatch(Transaction $transaction): void
    {
        $timeout      = (int)    config('services.fiscal_api.timeout');
        $baseUrl      = (string) config('services.fiscal_api.base_url');
        $dispatchPath = (string) config('services.fiscal_api.dispatch_path');

        if ($baseUrl === '') {
            Log::debug('Fiscal dispatch skipped: services.fiscal_api.base_url is not configured', [
                'transaction_id' => $transaction->id,
            ]);

            return;
        }

        $fiscalData = $transaction->fiscalData;

        if ($fiscalData === null) {
            Log::error('Fiscal dispatch aborted: missing fiscal data for transaction', [
                'transaction_id'   => $transaction->id,
                'transaction_uuid' => $transaction->transaction_uuid,
            ]);

            throw new RuntimeException(sprintf(
                'Transaction %d has no fiscal data to dispatch.',
                $transaction->id,
            ));
        }

        if ($fiscalData->fiscal_status !== FiscalStatus::PENDING && $fiscalData->fiscal_status !== FiscalStatus::PROCESSING) {
            return;
        }

        if ($fiscalData->fiscal_status === FiscalStatus::PENDING) {
            $this->fiscalDataRepository->markAsProcessing($fiscalData);
        }

        $dispatchUrl = rtrim($baseUrl, '/').'/'.ltrim($dispatchPath, '/');

        try {
            $response = Http::connectTimeout(5)
                ->timeout($timeout)
                ->withHeaders(['Idempotency-Key' => $transaction->idempotency_key])
                ->acceptJson()
                ->asJson()
                ->post($dispatchUrl, $this->buildRequestPayload($transaction));
        } catch (Throwable $exception) {
            Log::warning('Failed to reach fiscal service', [
                'transaction_id'   => $transaction->id,
                'transaction_uuid' => $transaction->transaction_uuid,
                'error_message'    => $exception->getMessage(),
                'error_class'      => $exception::class,
            ]);

            throw $exception;
        }

        if ($response->successful()) {
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

        $this->fiscalDataRepository->markAsRejected($fiscalData, [
            'error_message'     => self::REASON_FISCAL_REJECTED,
            'error_code'        => $response->status(),
            'fiscal_request_id' => $response->json('request_id'),
        ]);

        Log::warning('Fiscal service rejected transaction dispatch', [
            'transaction_id'    => $transaction->id,
            'transaction_uuid'  => $transaction->transaction_uuid,
            'response_status'   => $response->status(),
            'fiscal_request_id' => $response->json('request_id'),
            'fiscal_reason'     => $response->json('failure_reason'),
        ]);

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
