<?php

namespace App\Services\Auth;

use App\DTOs\Auth\AuthenticatedUser;
use App\DTOs\Auth\LoginData;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Models\User;
use App\Repositories\Auth\Contract\LoginUserRepositoryInterface;
use App\Services\Auth\Contracts\LoginServiceInterface;
use Illuminate\Support\Facades\Hash;

final class LoginService implements LoginServiceInterface
{
    private const TOKEN_NAME = 'api';

    public function __construct(
        private readonly LoginUserRepositoryInterface $loginUserRepository,
    ){}

    public function handle(LoginData $data): AuthenticatedUser
    {
        $user = $this->findUserByCpfOrCnpj($data->cpf ?? $data->cnpj);

        if (!$this->credentialsAreValid($user, $data)) 
        {
            throw new InvalidCredentialsException();
        }

        if (!$this->passwordMatches($user, $data)) 
        {
            throw new InvalidCredentialsException();
        }

        return new AuthenticatedUser(
            user:  $user,
            token: $user->createToken(self::TOKEN_NAME)->plainTextToken,
        );
    }

    private function findUserByCpfOrCnpj(string $cpfOrCnpj): ?User
    {
        return $this->loginUserRepository->findByCpfOrCnpj($cpfOrCnpj);
    }

    private function credentialsAreValid(?User $user, LoginData $data): bool
    {
        if ($user === null) 
        {
            return false;
        }

        return $this->documentMatches($user, $data);
    }

    private function documentMatches(User $user, LoginData $data): bool
    {
        if ($data->cpf !== null) 
        {
            return $user->cpf !== null && hash_equals((string) $user->cpf, $data->cpf);
        }

        if ($data->cnpj !== null) 
        {
            return $user->cnpj !== null && hash_equals((string) $user->cnpj, $data->cnpj);
        }

        return false;
    }

    private function passwordMatches(User $user, LoginData $data): bool
    {
        return Hash::check($data->password, $user->password);
    }
}
