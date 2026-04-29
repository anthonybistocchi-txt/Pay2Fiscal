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
            paymentAmount:         $validated['payment_amount'],
            user:                  Auth::user(),
            productId:             $validated['product_id'],
            paymentMethod:         $normalizedPaymentMethod,
            last4DigitsCardNumber: $validated['last_4_digits_card_number'] ?? null,
            cardBrand:             $normalizedCardBrand,
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
            'payment_amount'            => 'required|integer|min:1',
            'last_4_digits_card_number' => 'required_if:payment_method,credit_card,debit_card|digits:4',
            'card_brand'                => 'required_if:payment_method,credit_card,debit_card|string|in:visa,mastercard',
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
            'payment_amount.required'               => 'The payment amount is required',
            'payment_amount.integer'                => 'The payment amount must be an integer',
            'payment_amount.min'                    => 'The payment amount must be greater than 0',
            'last_4_digits_card_number.required_if' => 'The last 4 digits card number is required if the payment method is credit card or debit card',
            'last_4_digits_card_number.digits'      => 'The last 4 digits card number must be exactly 4 digits',
            'card_brand.required_if'                => 'The card brand is required if the payment method is credit card or debit card',
        ];
    }
}
