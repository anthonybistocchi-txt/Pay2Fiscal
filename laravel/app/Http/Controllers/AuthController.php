<?php

namespace App\Http\Controllers;

use App\DTOs\Auth\AuthenticatedUser;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\Auth\AuthenticatedUserResource;
use App\Http\Resources\Auth\UserDataResource;
use App\DTOs\Auth\UserData;
use App\Services\Auth\Contracts\LoginServiceInterface;
use App\Services\Auth\Contracts\LogoutServiceInterface;
use App\Services\Auth\Contracts\RegisterServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(
        private readonly LoginServiceInterface $loginService,
        private readonly RegisterServiceInterface $registerService,
        private readonly LogoutServiceInterface $logoutService,
    ) {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $authenticatedUser = $this->loginService->handle($request->toDto());

        return $this->authenticatedResponse($authenticatedUser, Response::HTTP_OK);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $userData = $this->registerService->handle($request->toDto());

        return $this->userDataResponse($userData, Response::HTTP_CREATED);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->logoutService->handle($request->user());

        return response()->json(['message' => 'Logged out.']);
    }

    private function authenticatedResponse(AuthenticatedUser $result, int $status): JsonResponse
    {
        return AuthenticatedUserResource::make($result)
            ->response()
            ->setStatusCode($status);
    }

    private function userDataResponse(UserData $userData, int $status): JsonResponse
    {
        return UserDataResource::make($userData)
            ->response()
            ->setStatusCode($status);
    }
}
