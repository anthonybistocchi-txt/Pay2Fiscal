<?php

namespace App\Repositories\Auth\Contract;

use App\Models\User;
use App\Repositories\Auth\DTO\RegisterUserInput;
use App\Repositories\Auth\DTO\RegisterUserUniqueness;

interface RegisterUserRepositoryInterface
{
    public function register(RegisterUserInput $input): User;

    public function userAlreadyExists(RegisterUserUniqueness $criteria): bool;
}
