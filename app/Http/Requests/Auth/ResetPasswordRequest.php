<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Body in camelCase for Scramble docs (passwordConfirmation).
     * validatedSnake() converts to snake_case for ResetPasswordData.
     *
     * @return array<string, array<int, string|\Illuminate\Validation\Rules\Password>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', Password::defaults()],
            'passwordConfirmation' => ['required', 'string', 'same:password'],
        ];
    }
}
