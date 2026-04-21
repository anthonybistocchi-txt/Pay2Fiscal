<?php

namespace App\DTOs\Auth;

/**
 * Immutable payload for the user data use case.
 */
class UserData
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $cpf = null,
        public readonly ?string $cnpj = null,
    ) {
    }
}