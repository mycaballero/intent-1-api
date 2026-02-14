<?php

namespace Tests\Unit\Services;

use App\Contracts\User\UserRepositoryInterface;
use App\Data\Auth\LoginData;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_user_and_token_when_credentials_valid(): void
    {
        $user = User::factory()->create([
            'email' => 'user@test.com',
            'password' => 'password',
        ]);

        $service = app(AuthService::class);
        $data = LoginData::from(['email' => 'user@test.com', 'password' => 'password']);
        $result = $service->login($data);

        $this->assertSame($user->id, $result['user']->id);
        $this->assertNotEmpty($result['token']);
    }

    public function test_login_throws_when_password_invalid(): void
    {
        User::factory()->create([
            'email' => 'user@test.com',
        ]);

        $service = app(AuthService::class);
        $data = LoginData::from(['email' => 'user@test.com', 'password' => 'wrong']);

        $this->expectException(ValidationException::class);
        $service->login($data);
    }

    public function test_login_throws_when_user_not_found(): void
    {
        $service = app(AuthService::class);
        $data = LoginData::from(['email' => 'missing@test.com', 'password' => 'password']);

        $this->expectException(ValidationException::class);

        $service->login($data);
    }
}
