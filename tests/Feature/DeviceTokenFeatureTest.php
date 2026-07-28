<?php

namespace Tests\Feature;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeviceTokenFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register_refresh_and_remove_an_encrypted_device_token(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_PENGGUNA,
            'is_active' => true,
        ]);
        Sanctum::actingAs($user);

        $token = 'fcm-token-'.str_repeat('x', 80);

        $this->postJson('/api/device-tokens', [
            'token' => $token,
            'platform' => 'android',
        ])->assertCreated()->assertJsonMissing(['token']);

        $this->assertDatabaseCount('device_tokens', 1);
        $this->assertNotSame(
            $token,
            DB::table('device_tokens')->value('token'),
        );
        $this->assertSame($token, DeviceToken::query()->sole()->token);

        $this->postJson('/api/device-tokens', [
            'token' => $token,
            'platform' => 'android',
        ])->assertOk();

        $this->assertDatabaseCount('device_tokens', 1);

        $this->deleteJson('/api/device-tokens', [
            'token' => $token,
        ])->assertOk();

        $this->assertDatabaseCount('device_tokens', 0);
    }

    public function test_employee_cannot_register_a_customer_push_token(): void
    {
        $employee = User::factory()->create([
            'role' => User::ROLE_KARYAWAN,
            'is_active' => true,
        ]);
        Sanctum::actingAs($employee);

        $this->postJson('/api/device-tokens', [
            'token' => 'not-a-customer-device-token',
            'platform' => 'android',
        ])->assertForbidden();
    }

    public function test_logout_removes_only_the_current_device_token(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_PENGGUNA,
            'is_active' => true,
        ]);
        Sanctum::actingAs($user);

        $removedToken = 'device-to-remove';
        $remainingToken = 'another-device';

        foreach ([$removedToken, $remainingToken] as $token) {
            DeviceToken::query()->create([
                'user_id' => $user->id,
                'token' => $token,
                'token_hash' => DeviceToken::hash($token),
                'platform' => 'android',
                'last_seen_at' => now(),
            ]);
        }

        $this->postJson('/api/logout', [
            'device_token' => $removedToken,
        ])->assertOk();

        $this->assertDatabaseMissing('device_tokens', [
            'token_hash' => DeviceToken::hash($removedToken),
        ]);
        $this->assertDatabaseHas('device_tokens', [
            'token_hash' => DeviceToken::hash($remainingToken),
        ]);
    }
}
