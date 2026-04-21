<?php

namespace App\Exceptions\Auth;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Thrown by the login use case when e-mail, password, or document do not match.
 *
 * A single generic message is returned so the API does not leak whether the
 * e-mail or the document actually exists in the database.
 */
class InvalidCredentialsException extends Exception
{
    public function __construct(string $message = 'Invalid credentials.')
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
