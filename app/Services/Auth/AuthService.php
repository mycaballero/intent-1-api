<?php

namespace App\Services\Auth;

use App\Contracts\Auth\AuthServiceInterface;
use App\Contracts\User\UserRepositoryInterface;
use App\Data\Auth\ForgotPasswordData;
use App\Data\Auth\LoginData;
use App\Data\Auth\ResetPasswordData;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    /**
     * @return array{user: \App\Models\User, token: string}
     */
    public function login(LoginData $data): array
    {
        $user = $this->userRepository->findByEmail($data->email);

        if (! $user || ! Hash::check($data->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $token = $user->createToken('api-login')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function sendResetLink(ForgotPasswordData $data): string
    {
        return Password::sendResetLink(['email' => $data->email]);
    }

    public function resetPassword(ResetPasswordData $data): string
    {
        return Password::reset(
            [
                'email' => $data->email,
                'password' => $data->password,
                'password_confirmation' => $data->password,
                'token' => $data->token,
            ],
            function ($user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
