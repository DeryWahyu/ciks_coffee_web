<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentProofAccessFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_payment_proof_is_available_only_to_the_order_owner(): void
    {
        Storage::fake('local');
        $customer = $this->customer();
        $otherCustomer = $this->customer();
        $order = $this->orderFor($customer);
        Storage::disk('local')->put($order->payment_proof, 'private-proof');

        Sanctum::actingAs($otherCustomer);
        $this->getJson("/api/orders/{$order->id}/payment-proof")
            ->assertForbidden();

        Sanctum::actingAs($customer);
        $this->get("/api/orders/{$order->id}/payment-proof")
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_employee_payment_proof_is_unavailable_to_another_assigned_employee(): void
    {
        Storage::fake('local');
        $customer = $this->customer();
        $assignedEmployee = $this->employee();
        $otherEmployee = $this->employee();
        $order = $this->orderFor($customer, $assignedEmployee);
        Storage::disk('local')->put($order->payment_proof, 'private-proof');

        $this->actingAs($otherEmployee)
            ->get("/karyawan/orders/{$order->id}/payment-proof")
            ->assertForbidden();

        $this->actingAs($assignedEmployee)
            ->get("/karyawan/orders/{$order->id}/payment-proof")
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    private function customer(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_PENGGUNA,
            'is_active' => true,
        ]);
    }

    private function employee(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_KARYAWAN,
            'is_active' => true,
        ]);
    }

    private function orderFor(User $customer, ?User $cashier = null): Order
    {
        return Order::create([
            'order_number' => 'CK-PROOF-' . str_pad((string) (Order::count() + 1), 4, '0', STR_PAD_LEFT),
            'customer_name' => $customer->name,
            'user_id' => $customer->id,
            'cashier_id' => $cashier?->id,
            'payment_method' => 'qris',
            'total' => 15000,
            'payment_proof' => 'payment_proofs/bukti.png',
            'status' => 'menunggu_verifikasi',
            'paid_at' => now(),
        ]);
    }
}
