<?php

namespace App\Services\Auth;

use App\DTOs\Auth\RegisterData;
use App\DTOs\Auth\UserData;
use App\Exceptions\Auth\UserAlreadyExistsException;
use App\Models\User;
use App\Repositories\Auth\Contract\RegisterUserRepositoryInterface;
use App\Repositories\Auth\DTO\RegisterUserInput;
use App\Repositories\Auth\DTO\RegisterUserUniqueness;
use App\Services\Auth\Contracts\RegisterServiceInterface;
use Illuminate\Database\ConnectionInterface;

final class RegisterService implements RegisterServiceInterface
{
    public function __construct(
        private readonly ConnectionInterface $database,
        private readonly RegisterUserRepositoryInterface $registerUserRepository,
    ){}

    public function handle(RegisterData $data): UserData
    {
        $user = $this->createUser($data);

        return new UserData(
            name:  $user->name,
            email: $user->email,
            cpf:   $user->cpf  ?? null,
            cnpj:  $user->cnpj ?? null,
        );
    }

    /**
     * Password hashing is handled by the User model cast ('password' => 'hashed').
     */
    private function createUser(RegisterData $data): User
    {
        return $this->database->transaction(function () use ($data) {

            if ($this->userAlreadyExists($data))
            {
                throw new UserAlreadyExistsException();
            }

            return $this->registerUserRepository->register(new RegisterUserInput(
                name:     $data->name,
                email:    $data->email,
                password: $data->password,
                cpf:      $data->cpf  ?? null,
                cnpj:     $data->cnpj ?? null,
            ));
        });
    }

    /**
     * True if email, CPF, or CNPJ is already taken (only non-null document fields are considered).
     */
    private function userAlreadyExists(RegisterData $data): bool
    {
        return $this->registerUserRepository->userAlreadyExists(new RegisterUserUniqueness(
            email: $data->email,
            cpf:   $data->cpf  ?? null,
            cnpj:  $data->cnpj ?? null,
        ));
    }

}
