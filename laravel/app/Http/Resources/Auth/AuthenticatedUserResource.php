<?php

namespace App\Http\Resources\Auth;

use App\DTOs\Auth\AuthenticatedUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API representation of the AuthenticatedUser DTO.
 */
class AuthenticatedUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var AuthenticatedUser $authenticated */
        $authenticated = $this->resource;

        return [
            'token'      => $authenticated->token,
            'token_type' => 'Bearer',
            'user' => [
                'id'    => $authenticated->user->id,
                'name'  => $authenticated->user->name,
                'email' => $authenticated->user->email,
                'cpf'   => $authenticated->user->cpf,
                'cnpj'  => $authenticated->user->cnpj,
            ],
        ];
    }
}
