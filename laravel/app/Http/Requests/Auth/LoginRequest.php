<?php

namespace App\Http\Requests\Auth;

use App\DTOs\Auth\LoginData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    private const CPF_DIGIT_COUNT = 11;

    private const CNPJ_DIGIT_COUNT = 14;

    private const PASSWORD_MIN_LENGTH = 8;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeEmailInput();
        $this->normalizeDocumentDigits('cpf');
        $this->normalizeDocumentDigits('cnpj');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(
            $this->credentialRules(),
            $this->brazilianDocumentRules(),
        );
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(
            $this->credentialMessages(),
            $this->brazilianDocumentMessages(),
        );
    }

    /**
     * Map validated input into the use-case DTO.
     */
    public function toDto(): LoginData
    {
        $validated = $this->validated();

        return new LoginData(
            email:    $validated['email'],
            password: $validated['password'],
            cpf:      $validated['cpf'] ?? null,
            cnpj:     $validated['cnpj'] ?? null,
        );
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    private function credentialRules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc'],
            'password' => ['required', 'string', 'min:'.self::PASSWORD_MIN_LENGTH],
        ];
    }

    /**
     * Brazilian tax ID: exactly one of CPF (individual) or CNPJ (company).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    private function brazilianDocumentRules(): array
    {
        $cpfPattern = '/^\d{'.self::CPF_DIGIT_COUNT.'}$/';
        $cnpjPattern = '/^\d{'.self::CNPJ_DIGIT_COUNT.'}$/';

        return [
            'cpf' => [
                'nullable',
                'string',
                'size:'.self::CPF_DIGIT_COUNT,
                'regex:'.$cpfPattern,
                'required_without:cnpj',
                'prohibits:cnpj',
            ],
            'cnpj' => [
                'nullable',
                'string',
                'size:'.self::CNPJ_DIGIT_COUNT,
                'regex:'.$cnpjPattern,
                'required_without:cpf',
                'prohibits:cpf',
            ],
        ];
    }

    private function normalizeEmailInput(): void
    {
        if (! $this->has('email') || ! is_string($this->email)) {
            return;
        }

        $this->merge(['email' => mb_strtolower(trim($this->email))]);
    }

    private function normalizeDocumentDigits(string $field): void
    {
        if (! $this->has($field)) {
            return;
        }

        $raw = $this->input($field);

        if ($raw === null || $raw === '') {
            $this->merge([$field => null]);

            return;
        }

        $onlyDigits = preg_replace('/\D/', '', (string) $raw);
        $this->merge([$field => $onlyDigits === '' ? null : $onlyDigits]);
    }

    /**
     * Messages for email and password (matches credentialRules).
     *
     * @return array<string, string>
     */
    private function credentialMessages(): array
    {
        return [
            'email.required' => 'The email address is required.',
            'email.string'   => 'The email address must be a string.',
            'email.email'    => 'The email address must be a valid email address.',

            'password.required' => 'The password is required.',
            'password.string'   => 'The password must be a string.',
            'password.min'      => 'The password must be at least :min characters.',
        ];
    }

    /**
     * Messages for CPF/CNPJ (matches brazilianDocumentRules).
     *
     * @return array<string, string>
     */
    private function brazilianDocumentMessages(): array
    {
        return [
            'cpf.required_without' => 'Either CPF or CNPJ is required.',
            'cpf.string'           => 'The CPF must be a string.',
            'cpf.size'             => 'The CPF must be exactly '.self::CPF_DIGIT_COUNT.' digits.',
            'cpf.regex'            => 'The CPF must contain only digits.',
            'cpf.prohibits'        => 'Provide either CPF or CNPJ, not both.',

            'cnpj.required_without' => 'Either CPF or CNPJ is required.',
            'cnpj.string'           => 'The CNPJ must be a string.',
            'cnpj.size'             => 'The CNPJ must be exactly '.self::CNPJ_DIGIT_COUNT.' digits.',
            'cnpj.regex'            => 'The CNPJ must contain only digits.',
            'cnpj.prohibits'        => 'Provide either CPF or CNPJ, not both.',
        ];
    }
}
