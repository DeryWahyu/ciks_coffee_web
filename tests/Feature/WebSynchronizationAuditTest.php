<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\ShopSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WebSynchronizationAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_revision_changes_when_mobile_pickup_removes_a_ready_order(): void
    {
        $employee = $this->user('karyawan');
        $customer = $this->user('pengguna');
        $order = Order::create([
            'order_number' => 'CK-SYNC-'.Str::uuid(), 'customer_name' => $customer->name,
            'user_id' => $customer->id, 'cashier_id' => $employee->id,
            'payment_method' => 'qris', 'total' => 15000, 'status' => 'selesai', 'paid_at' => now(),
        ]);
        $revision = $this->actingAs($employee, 'web')->getJson(route('karyawan.orders.revision'))->assertOk()->json('revision');
        $this->get(route('karyawan.orders.index'))->assertOk()->assertViewHas('revision', $revision)->assertSeeText($order->order_number);
        Sanctum::actingAs($customer);
        $this->postJson('/api/orders/'.$order->id.'/pickup')->assertOk();
        $next = $this->actingAs($employee, 'web')->getJson(route('karyawan.orders.revision'))->assertOk()->json('revision');
        $this->assertNotSame($revision, $next);
        $this->get(route('karyawan.orders.index'))->assertDontSeeText($order->order_number);
        $this->get(route('karyawan.orders.history'))->assertSeeText($order->order_number);
        $this->actingAs($this->user('pemilik'))->getJson(route('karyawan.orders.revision'))->assertForbidden();
    }

    public function test_pos_and_mobile_read_the_same_current_qris_image_and_deletion(): void
    {
        Storage::fake('public');
        $owner = $this->user('pemilik');
        $this->actingAs($owner)->post(route('pemilik.settings.qris.update'), ['qris_image' => UploadedFile::fake()->image('qris.png')])
            ->assertRedirect();
        $path = ShopSetting::getValue('qris_image');
        $this->actingAs($this->user('karyawan'), 'web')->getJson(route('karyawan.pos.payment-settings'))
            ->assertOk()->assertJsonPath('image_url', asset('storage/'.$path));
        Sanctum::actingAs($this->user('pengguna'));
        $this->getJson('/api/shop/qris')->assertOk()->assertJsonPath('data.image_url', 'storage/'.$path);
        $this->actingAs($owner, 'web')->delete(route('pemilik.settings.qris.delete'))->assertRedirect();
        Storage::disk('public')->assertMissing($path);
        $this->actingAs($this->user('karyawan'), 'web')->getJson(route('karyawan.pos.payment-settings'))->assertJsonPath('image_url', null);
        Sanctum::actingAs($this->user('pengguna'));
        $this->getJson('/api/shop/qris')->assertJsonPath('success', false);
    }

    public function test_date_filters_reject_invalid_or_reversed_ranges_and_allow_blank_defaults(): void
    {
        $this->actingAs($this->user('pemilik'));
        foreach (['pemilik.reports.sales', 'pemilik.performance.employees', 'pemilik.performance.products'] as $route) {
            $this->getJson(route($route, ['start_date' => 'invalid-date']))->assertUnprocessable();
            $this->getJson(route($route, ['start_date' => '2026-10-25', 'end_date' => '2026-10-01']))->assertUnprocessable();
            $this->get(route($route, ['start_date' => '', 'end_date' => '']))->assertOk();
        }
        foreach (['pemilik.reports.transactions', 'pemilik.exports.csv', 'pemilik.exports.excel', 'pemilik.exports.pdf'] as $route) {
            $this->getJson(route($route, ['date_from' => '2026-02-31']))->assertUnprocessable();
        }
        $this->actingAs($this->user('karyawan'));
        foreach (['karyawan.orders.history', 'karyawan.income.index'] as $route) {
            $this->getJson(route($route, ['date_from' => '2026-10-25', 'date_to' => '2026-10-01']))->assertUnprocessable();
        }
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    public function test_password_change_invalidates_an_existing_pos_browser_session(): void
    {
        $employee = $this->user('karyawan');
        $this->actingAs($employee)->get(route('karyawan.pos.index'))->assertOk();
        $employee->update(['password' => 'changed-password']);
        $this->getJson(route('karyawan.pos.index'))->assertUnauthorized();
    }

    public function test_deactivation_cannot_strand_orders_assigned_to_a_cashier(): void
    {
        $employee = $this->user('karyawan');
        $order = Order::create(['order_number' => 'CK-ACTIVE-EMPLOYEE', 'customer_name' => 'Pembeli POS',
            'user_id' => $employee->id, 'cashier_id' => $employee->id, 'payment_method' => 'cash',
            'total' => 15000, 'status' => 'antrian_baru', 'paid_at' => now()]);
        $owner = $this->user('pemilik');
        $this->actingAs($owner)->patch(route('pemilik.users.toggle-status', $employee))->assertSessionHas('error');
        $this->assertTrue($employee->fresh()->is_active);
        $order->updateQuietly(['status' => 'selesai']);
        $this->patch(route('pemilik.users.toggle-status', $employee))->assertSessionHas('success');
        $this->assertFalse($employee->fresh()->is_active);
        $this->assertSame($employee->id, $order->fresh()->cashier_id);
    }
}
