<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_succeeds_with_valid_token(): void
    {
        $user = User::factory()->create(['email' => 'user@test.com']);
        $token = Password::broker()->createToken($user);

        $payload = [
            'email' => 'user@test.com',
            'token' => $token,
            'password' => 'newpassword',
            'passwordConfirmation' => 'newpassword',
        ];

        $response = $this->postJson('/api/v1/auth/reset-password', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure(['message']);

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword', $user->password));
    }

    public function test_reset_password_requires_email_token_and_password(): void
    {
        $response = $this->postJson('/api/v1/auth/reset-password', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'token', 'password', 'passwordConfirmation']);
    }

    public function test_reset_password_requires_password_confirmation_match(): void
    {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'user@test.com',
            'token' => 'any-token',
            'password' => 'newpassword',
            'passwordConfirmation' => 'different',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['passwordConfirmation']);
    }
}
