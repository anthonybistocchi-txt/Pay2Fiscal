<?php

namespace App\Repositories\Auth;

use App\Models\User;
use App\Repositories\Auth\Contract\RegisterUserRepositoryInterface;
use App\Repositories\Auth\DTO\RegisterUserInput;
use App\Repositories\Auth\DTO\RegisterUserUniqueness;

final class RegisterUserRepository implements RegisterUserRepositoryInterface
{
    public function register(RegisterUserInput $input): User
    {
        return User::create([
            'name'     => $input->name,
            'email'    => $input->email,
            'password' => $input->password,
            'cpf'      => $input->cpf  ?? null,
            'cnpj'     => $input->cnpj ?? null,
        ]);
    }

    public function userAlreadyExists(RegisterUserUniqueness $user): bool
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
