<?php

namespace App\Exceptions\Auth;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Thrown by the register use case when a user already exists.
 * Generic wording avoids account enumeration.
 */
class UserAlreadyExistsException extends Exception
{
    public function __construct(string $message = 'Registration could not be completed with the provided data')
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json(
            ['message' => $this->getMessage()],
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }
}