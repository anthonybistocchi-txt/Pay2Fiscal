<?php

namespace App\DTOs\Auth;

use App\Models\User;

/**
 * Result returned by login/register use cases.
 */
final class AuthenticatedUser
{
    public function __construct(
        public readonly User $user,
        public readonly string $token,
    ) {
    }
}
