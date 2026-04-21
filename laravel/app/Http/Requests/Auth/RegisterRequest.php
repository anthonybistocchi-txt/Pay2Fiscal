<?php

namespace App\Http\Requests\Auth;

use App\DTOs\Auth\RegisterData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    private const CPF_DIGIT_COUNT = 11;

    private const CNPJ_DIGIT_COUNT = 14;

    private const MAX_NAME_LENGTH = 255;

    private const MAX_EMAIL_LENGTH = 255;

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
            $this->accountRegistrationRules(),
            $this->brazilianDocumentRules(),
        );
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(
            $this->accountRegistrationMessages(),
            $this->brazilianDocumentMessages(),
        );
    }

    /**
     * Map validated input into the use-case DTO.
     */
    public function toDto(): RegisterData
    {
        $validated = $this->validated();

        return new RegisterData(
            name:     $validated['name'],
            email:    $validated['email'],
            password: $validated['password'],
            cpf:      $validated['cpf']  ?? null,
            cnpj:     $validated['cnpj'] ?? null,
        );
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    private function accountRegistrationRules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:'.self::MAX_NAME_LENGTH],
            'email'    => ['required', 'string', 'email:rfc', 'max:'.self::MAX_EMAIL_LENGTH],
            'password' => ['required', 'string', 'confirmed', Password::min(self::PASSWORD_MIN_LENGTH)],
        ];
    }

    /**
     * Same document rule as login. Duplicate email/cpf/cnpj is enforced in {@see \App\Services\Auth\RegisterService}.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    private function brazilianDocumentRules(): array
    {
        $cpfPattern  = '/^\d{'.self::CPF_DIGIT_COUNT.'}$/';
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
     * Messages for name, email, and password (matches accountRegistrationRules).
     *
     * Password rule is Password::min(8), which validates as string + min; other Password::* criteria are not enabled.
     *
     * @return array<string, string>
     */
    private function accountRegistrationMessages(): array
    {
        return [
            'name.required' => 'the name is required',
            'name.string'   => 'the name must be a string',
            'name.max'      => 'the name must not exceed :max characters',

            'email.required' => 'the email address is required',
            'email.string'   => 'the email address must be a string',
            'email.email'    => 'the email address must be a valid email address',
            'email.max'      => 'the email address must not exceed :max characters',

            'password.required'  => 'the password is required',
            'password.string'    => 'the password must be a string',
            'password.min'       => 'the password must be at least :min characters',
            'password.confirmed' => 'the password and confirmation do not match',
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
            'cpf.required_without' => 'either CPF or CNPJ is required',
            'cpf.string'           => 'the CPF must be a string',
            'cpf.size'             => 'the CPF must be exactly '.self::CPF_DIGIT_COUNT.' digits',
            'cpf.regex'            => 'the CPF must contain only digits',
            'cpf.prohibits'        => 'provide either CPF or CNPJ, not both',

            'cnpj.required_without' => 'either CPF or CNPJ is required',
            'cnpj.string'           => 'the CNPJ must be a string',
            'cnpj.size'             => 'the CNPJ must be exactly '.self::CNPJ_DIGIT_COUNT.' digits',
            'cnpj.regex'            => 'the CNPJ must contain only digits',
            'cnpj.prohibits'        => 'provide either CPF or CNPJ, not both.',
        ];
    }
}
