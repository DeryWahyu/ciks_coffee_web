<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class ApiAuthTokenSecurityFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_login_issues_an_expiring_token(): void
    {
        Carbon::setTestNow('2026-07-26 12:00:00');
        config()->set('sanctum.expiration', 60);
        $expectedExpiry = now()->addHour()->toIso8601String();
        $user = User::factory()->create([
            'email' => 'pelanggan@example.test',
            'password' => 'password',
            'role' => User::ROLE_PENGGUNA,
            'is_active' => true,
        ]);

        try {
            $this->postJson('/api/login', [
                'email' => $user->email,
                'password' => 'password',
            ])
                ->assertOk()
                ->assertJsonPath('token_type', 'Bearer')
                ->assertJsonPath('expires_at', $expectedExpiry);

            $token = PersonalAccessToken::query()->sole();
            $this->assertSame('mobile_auth_token', $token->name);
            $this->assertSame('2026-07-26 13:00:00', $token->expires_at?->format('Y-m-d H:i:s'));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_registration_does_not_issue_an_unused_bearer_token(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Pelanggan Baru',
            'email' => 'baru@example.test',
            'password' => 'password123',
        ])
            ->assertCreated()
            ->assertJsonMissing(['access_token']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
        $expectedExpiry = now()->addHour()->toIso8601String();
