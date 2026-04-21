<?php

namespace App\Services\Auth\Contracts;

use App\DTOs\Auth\UserData;
use App\DTOs\Auth\RegisterData;

interface RegisterServiceInterface
{
    /**
     * Persist a new user.
     */
    public function handle(RegisterData $data): UserData;
}
