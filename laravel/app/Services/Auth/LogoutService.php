<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Auth\Contracts\LogoutServiceInterface;
use Laravel\Sanctum\PersonalAccessToken;

final class LogoutService implements LogoutServiceInterface
{
    /**
     * Deletes only the token attached to the current request.
     *
     * TransientToken (used for SPA cookie sessions) is not a persisted model
     * and therefore cannot be revoked here — the client must drop the cookie.
     */
    public function handle(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }
}
