<?php

namespace App\Contracts\Auth;

use App\Data\Auth\ForgotPasswordData;
use App\Data\Auth\LoginData;
use App\Data\Auth\ResetPasswordData;
use App\Models\User;

interface AuthServiceInterface
{
    /**
     * Attempt login; returns user and plain-text token or throws.
     *
     * @return array{user: User, token: string}
     */
    public function login(LoginData $data): array;

    /**
     * Send password reset link to email. Returns status key (e.g. Password::RESET_LINK_SENT).
     */
    public function sendResetLink(ForgotPasswordData $data): string;

    /**
     * Reset password with token. Returns status key (e.g. Password::PASSWORD_RESET).
     */
    public function resetPassword(ResetPasswordData $data): string;
}
