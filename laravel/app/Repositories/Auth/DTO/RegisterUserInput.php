<?php

namespace App\Repositories\Auth\DTO;

/**
 * Payload for persisting a new user (repository boundary).
 */
final class RegisterUserInput
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly ?string $cpf = null,
        public readonly ?string $cnpj = null,
    ) {
    }
}
