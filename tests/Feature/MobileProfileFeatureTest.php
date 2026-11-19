<?php

namespace Tests\Feature;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileProfileFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function customer(array $attributes = []): User
    {
        return User::factory()->create(['role' => 'pengguna', 'is_active' => true, 'password' => 'password123', ...$attributes]);
    }

    public function test_profile_and_password_require_authentication_and_customer_role(): void
    {
        $this->patchJson('/api/user', [])->assertUnauthorized();
        $this->putJson('/api/user/password', [])->assertUnauthorized();
        foreach (['pemilik', 'karyawan'] as $role) {
            $user = $this->customer(['role' => $role]);
            $this->withToken($user->createToken('other-role')->plainTextToken);
            $this->patchJson('/api/user', [])->assertForbidden();
            $this->putJson('/api/user/password', [])->assertForbidden();
        }
    }

    public function test_customer_updates_only_own_profile_and_phone_is_normalized(): void
    {
        $customer = $this->customer();
        $other = $this->customer(['name' => 'Tetap sama']);
        $this->withToken($customer->createToken('current')->plainTextToken)
            ->patchJson('/api/user', ['name' => '  Nama Baru  ', 'email' => $customer->email, 'phone' => '+62 812-3456-7890'])
            ->assertOk()->assertJsonPath('user.id', $customer->id)->assertJsonPath('user.name', 'Nama Baru')
            ->assertJsonPath('user.phone', '+6281234567890')->assertJsonMissingPath('user.password');
        $this->assertSame('Nama Baru', $customer->fresh()->name);
        $this->assertSame('Tetap sama', $other->fresh()->name);
        $this->patchJson('/api/user', ['name' => 'Nama Baru', 'email' => $customer->email, 'phone' => ''])->assertOk()->assertJsonPath('user.phone', null);
        $this->patchJson('/api/user', ['name' => 'Nama Baru', 'email' => $customer->email, 'phone' => 'nomor-palsu'])->assertUnprocessable()->assertJsonValidationErrors('phone')->assertDontSee('validation.');
    }

    public function test_profile_cannot_change_privileges_password_or_another_user(): void
    {
        $customer = $this->customer();
        $this->withToken($customer->createToken('current')->plainTextToken)
            ->patchJson('/api/user', ['name' => 'Ditolak', 'email' => $customer->email, 'role' => 'pemilik', 'is_active' => false, 'user_id' => 999, 'password' => 'malicious123'])
            ->assertUnprocessable()->assertJsonValidationErrors(['role', 'is_active', 'user_id', 'password']);
        $this->assertSame('pengguna', $customer->fresh()->role);
        $this->assertTrue($customer->is_active);
        $this->assertTrue(Hash::check('password123', $customer->password));
    }

    public function test_changing_email_requires_current_password_and_a_unique_email(): void
    {
        $customer = $this->customer();
        $other = $this->customer();
        $this->withToken($customer->createToken('current')->plainTextToken);
        $body = ['name' => $customer->name, 'email' => 'baru@example.test'];
        foreach ([null, 'salah'] as $password) {
            $this->patchJson('/api/user', [...$body, 'current_password' => $password])->assertUnprocessable()->assertJsonValidationErrors('current_password');
            $this->assertNotSame('baru@example.test', $customer->fresh()->email);
        }
        $this->patchJson('/api/user', [...$body, 'email' => $other->email, 'current_password' => 'password123'])->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->patchJson('/api/user', [...$body, 'current_password' => 'password123'])->assertOk()->assertJsonPath('user.email', 'baru@example.test');
        $this->assertNull($customer->fresh()->email_verified_at);
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/user')->assertOk()->assertJsonPath('email', 'baru@example.test');
    }

    public function test_invalid_password_change_preserves_password_and_sessions(): void
    {
        $customer = $this->customer();
        $this->withToken($customer->createToken('current')->plainTextToken);
        $customer->createToken('other');
        $body = ['current_password' => 'salah', 'password' => 'new-password123', 'password_confirmation' => 'new-password123'];
        $this->putJson('/api/user/password', $body)->assertUnprocessable()->assertJsonValidationErrors('current_password');
        $this->putJson('/api/user/password', [...$body, 'current_password' => 'password123', 'password_confirmation' => 'mismatch'])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->putJson('/api/user/password', ['current_password' => 'password123', 'password' => 'password123', 'password_confirmation' => 'password123'])->assertUnprocessable();
        $this->assertTrue(Hash::check('password123', $customer->fresh()->password));
        $this->assertSame(2, $customer->tokens()->count());
    }

    public function test_password_change_keeps_current_session_and_revokes_other_sessions_only(): void
    {
        $customer = $this->customer();
        $token = $customer->createToken('current');
        $otherToken = $customer->createToken('other');
        $otherCustomer = $this->customer();
        $foreignToken = $otherCustomer->createToken('foreign');
        $currentDevice = $customer->deviceTokens()->create(['token' => 'current-device', 'token_hash' => DeviceToken::hash('current-device'), 'platform' => 'android']);
        $otherDevice = $customer->deviceTokens()->create(['token' => 'other-device', 'token_hash' => DeviceToken::hash('other-device'), 'platform' => 'android']);
        $foreignDevice = $otherCustomer->deviceTokens()->create(['token' => 'foreign-device', 'token_hash' => DeviceToken::hash('foreign-device'), 'platform' => 'android']);
        $this->withToken($token->plainTextToken)->putJson('/api/user/password', [
            'current_password' => 'password123', 'password' => 'new-password123', 'password_confirmation' => 'new-password123',
            'device_token' => 'current-device',
        ])->assertOk()->assertJsonPath('message', 'Kata sandi berhasil diperbarui. Sesi di perangkat lain telah diakhiri.');
        $this->assertTrue(Hash::check('new-password123', $customer->fresh()->password));
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $token->accessToken->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $otherToken->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $foreignToken->accessToken->id]);
        $this->assertDatabaseHas('device_tokens', ['id' => $currentDevice->id]);
        $this->assertDatabaseMissing('device_tokens', ['id' => $otherDevice->id]);
        $this->assertDatabaseHas('device_tokens', ['id' => $foreignDevice->id]);
        $this->getJson('/api/user')->assertOk();
        $this->postJson('/api/login', ['email' => $customer->email, 'password' => 'password123'])->assertUnprocessable();
        $this->postJson('/api/login', ['email' => $customer->email, 'password' => 'new-password123'])->assertOk();
        $this->assertDatabaseCount('orders', 0);
    }
}
