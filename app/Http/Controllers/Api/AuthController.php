<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Auth\AuthServiceInterface;
use App\Data\Auth\ForgotPasswordData;
use App\Data\Auth\LoginData;
use App\Data\Auth\ResetPasswordData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    public function __construct(
        private AuthServiceInterface $authService
    ) {}

    /**
     * Login: validate credentials, issue Sanctum token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $data = LoginData::from($request->validatedSnake());
        $result = $this->authService->login($data);

        return response()->json([
            'token' => $result['token'],
            'tokenType' => 'Bearer',
            'user' => new UserResource($result['user']),
        ]);
    }

    /**
     * Forgot password: send reset link to email.
     */
    public function sendResetLink(ForgotPasswordRequest $request): JsonResponse
    {
        $data = ForgotPasswordData::from($request->validatedSnake());
        $status = $this->authService->sendResetLink($data);

        if ($status !== Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => __($status),
            ], 422);
        }

        return response()->json([
            'message' => __($status),
        ]);
    }

    /**
     * Reset password: set new password using token from email link.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $data = ResetPasswordData::from($request->validatedSnake());
        $status = $this->authService->resetPassword($data);

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'message' => __($status),
            ], 422);
        }

        return response()->json([
            'message' => __($status),
        ]);
    }

    /**
     * Logout: revoke current access token. Requires Authorization: Bearer {token}.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'message' => __('auth.logged_out'),
        ]);
    }
}
