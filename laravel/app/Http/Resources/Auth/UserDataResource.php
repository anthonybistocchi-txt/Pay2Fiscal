<?php

namespace App\Http\Resources\Auth;

use App\DTOs\Auth\UserData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserDataResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var UserData $user */
        $user = $this->resource;

        return [
            'name'  => $user->name,
            'email' => $user->email,
            'cpf'   => $user->cpf  ?? null,
            'cnpj'  => $user->cnpj ?? null,
        ];
    }
}