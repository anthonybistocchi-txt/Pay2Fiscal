<?php

namespace App\Repositories\Auth;

use App\Repositories\Auth\Contract\LoginUserRepositoryInterface;
use App\Repositories\Auth\DTO\LoginData;
use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
class LoginUserRepository implements LoginUserRepositoryInterface
{
    public function findByCpfOrCnpj(string $cpfOrCnpj): ?User
    {
        return User::where('cpf', $cpfOrCnpj)->orWhere('cnpj', $cpfOrCnpj)->first();
    }

}