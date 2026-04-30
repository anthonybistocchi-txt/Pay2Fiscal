<?php

namespace App\Http\Requests;

use App\DTOs\Purchase\PurchaseStoreData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class PurchaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Promote the Idempotency-Key HTTP header (RFC draft) into the request input
     * so it can be validated by the standard rules. Body still works as fallback
     * for clients that cannot set custom headers.
     */
    protected function prepareForValidation(): void
    {
        $headerKey = $this->header('Idempotency-Key');

        if (is_string($headerKey) && $headerKey !== '') {
            $this->merge(['idempotency_key' => $headerKey]);
        }
    }

    public function toDto(): PurchaseStoreData
    {
        $validated = $this->validated();

        $normalizedPaymentMethod = match ($validated['payment_method']) {
            'credit_card' => 'CREDIT_CARD',
            'debit_card'  => 'DEBIT_CARD',
            'pix'         => 'PIX',
            'boleto'      => 'BOLETO',
        };

        $normalizedCardBrand = isset($validated['card_brand'])
            ? strtoupper($validated['card_brand'])
            : null;

        return new PurchaseStoreData(
            quantity:              $validated['quantity'],
            user:                  Auth::user(),
            productId:             $validated['product_id'],
            paymentMethod:         $normalizedPaymentMethod,
            last4DigitsCardNumber: $validated['last_4_digits_card_number'] ?? null,
            cardBrand:             $normalizedCardBrand,
            idempotencyKey:        $validated['idempotency_key'],
        );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quantity'                  => 'required|integer|min:1',
            'product_id'                => 'required|integer|exists:products,id',
            'payment_method'            => 'required|string|in:credit_card,debit_card,pix,boleto',
            'last_4_digits_card_number' => 'required_if:payment_method,credit_card,debit_card|digits:4',
            'card_brand'                => 'required_if:payment_method,credit_card,debit_card|string|in:visa,mastercard',
            'idempotency_key'           => 'required|string|uuid',
        ];

    }

    public function messages(): array
    {
        return [
            'quantity.required'                     => 'The quantity is required',
            'quantity.integer'                      => 'The quantity must be an integer',
            'quantity.min'                          => 'The quantity must be greater than 0',
            'product_id.integer'                    => 'The product id must be an integer',
            'product_id.exists'                     => 'The product id does not exist',
            'product_id.required'                   => 'The product id is required',
            'payment_method.required'               => 'The payment method is required',
            'payment_method.string'                 => 'The payment method must be a string',
            'payment_method.in'                     => 'The payment method must be a valid payment method',
            'last_4_digits_card_number.required_if' => 'The last 4 digits card number is required if the payment method is credit card or debit card',
            'last_4_digits_card_number.digits'      => 'The last 4 digits card number must be exactly 4 digits',
            'card_brand.required_if'                => 'The card brand is required if the payment method is credit card or debit card',
            'idempotency_key.required'              => 'The idempotency key is required',
            'idempotency_key.string'                => 'The idempotency key must be a string',
            'idempotency_key.uuid'                  => 'The idempotency key must be a valid uuid',
        ];
    }
}
