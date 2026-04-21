<?php

namespace App\Repositories\Auth\DTO;

/**
 * Fields used to check email / CPF / CNPJ uniqueness before insert.
 */
final class RegisterUserUniqueness
{
    public function __construct(
        public readonly string $email,
        public readonly ?string $cpf = null,
        public readonly ?string $cnpj = null,
    ) {
    }
}
