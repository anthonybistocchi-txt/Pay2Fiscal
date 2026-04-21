<?php

namespace App\DTOs\Auth;

/**
 * Immutable payload for the register use case.
 *
 * Exactly one of {cpf, cnpj} must be provided (enforced at the request layer).
 */
final class RegisterData
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
