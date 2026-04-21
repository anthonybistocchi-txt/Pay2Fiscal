<?php

namespace App\Services\Auth\Contracts;

use App\Models\User;

interface LogoutServiceInterface
{
    /**
     * Revoke the access token currently being used by the authenticated user.
     */
    public function handle(User $user): void;
}
