<?php

namespace App\Repositories\Auth\Contract;

use App\Models\User;

interface RegisterUserRepositoryInterface
{
    public function register(User $user): User;
    public function userAlreadyExists(User $user): bool;
}