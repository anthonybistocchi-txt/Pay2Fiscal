<?php

namespace App\Services\Auth\Contracts;

use App\DTOs\Auth\AuthenticatedUser;
use App\DTOs\Auth\LoginData;

interface LoginServiceInterface
{
    /**
     * Authenticate the user and issue an access token.
     *
     * @throws \App\Exceptions\Auth\InvalidCredentialsException when credentials do not match
     */
    public function handle(LoginData $data): AuthenticatedUser;
}
