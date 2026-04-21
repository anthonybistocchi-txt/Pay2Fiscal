<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PurchaseStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount'                                => 'required|numeric|min:0.01',
            'currency'                              => 'required|string|in:BRL',
            'description'                           => 'required|string|max:255',
            'card_number'                           => 'required|string|max:16',
            'card_holder_name'                      => 'required|string|max:255',
            'card_expiration_date'                  => 'required|string|max:5',
            'card_cvv'                              => 'required|string|max:4',
            'card_brand'                            => 'required|string|in:VISA,MASTERCARD,AMEX,ELO,HIPERCARD,HIPER,DINERS,DISCOVER,JCB,MAESTRO,PLENO,SANTANDER,SOROCRED,TICKET',
            'card_type'                             => 'required|string|in:CREDIT,DEBIT,PREPAID',
            'card_country'                          => 'required|string|max:2',
            'card_ip'                               => 'required|string|ipv4',
            'card_user_agent'                       => 'required|string|max:255',
            'card_acceptance_token'                 => 'required|string|max:255',
            'card_acceptance_token_expiration_date' => 'required|date|after:now',
            'user_id'                               => 'required|exists:users,id',
            'user_email'                            => 'required|email|exists:users,email',
            'user_name'                             => 'required|string|max:255',
            'user_document'                         => 'required|string|max:14',
            'user_phone'                            => 'required|string|max:15',
            'user_address'                          => 'required|string|max:255',
            'user_city'                             => 'required|string|max:255',
            'user_state'                            => 'required|string|max:2',
            'user_zip_code'                         => 'required|string|max:8',
        ];
    }
}
