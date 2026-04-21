<?php

namespace App\Repositories\Auth;

use App\Models\User;
use App\Repositories\Auth\Contract\RegisterUserRepositoryInterface;

final class RegisterUserRepository implements RegisterUserRepositoryInterface
{
    public function register(User $user): User
    {
        return User::create([
            'name'     => $user->name,
            'email'    => $user->email,
            'password' => $user->password,
            'cpf'      => $user->cpf  ?? null,
            'cnpj'     => $user->cnpj ?? null,
        ]);
    }

    public function userAlreadyExists(User $user): bool
    {
        return User::query()
            ->where(function ($query) use ($user): void {
                $query->where('email', $user->email)

                ->when($user->cpf !== null, function ($query) use ($user): void {
                    $query->orWhere('cpf', $user->cpf);
                })
                
                ->when($user->cnpj !== null, function ($query) use ($user): void {
                    $query->orWhere('cnpj', $user->cnpj);
                });
            })
            ->exists();
    }
}