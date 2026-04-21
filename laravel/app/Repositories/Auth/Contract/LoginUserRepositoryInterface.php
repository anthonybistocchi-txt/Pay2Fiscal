<?php

namespace App\Repositories\Auth\Contract;

use App\Models\User;

interface LoginUserRepositoryInterface
{
    public function findByCpfOrCnpj(string $cpfOrCnpj): ?User;
}