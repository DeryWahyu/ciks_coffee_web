<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\MobileCheckout;
use App\Models\User;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class MobileCheckoutService
{
    public static function fingerprint(array $items): string
    {
        $items = array_map(fn ($i) => ['product_id' => (int) $i['product_id'], 'variant' => $i['variant'] ?? null, 'quantity' => (int) $i['quantity']], $items);
        usort($items, fn ($a, $b) => [$a['product_id'], $a['variant'], $a['quantity']] <=> [$b['product_id'], $b['variant'], $b['quantity']]);

        return hash('sha256', json_encode($items, JSON_THROW_ON_ERROR));
    }

    public function reserve(User $user, array $items, string $key, mixed $expectedTotal): MobileCheckout
    {
        return DB::transaction(function () use ($user, $items, $key, $expectedTotal) {
            $customer = User::query()->lockForUpdate()->findOrFail($user->id);
            abort_unless($customer->is_active && $customer->isPengguna(), 403);
            $fingerprint = self::fingerprint($items);
            $existing = MobileCheckout::where('user_id', $customer->id)->where('request_key', $key)->first();
            if ($existing) {
                if (! hash_equals($existing->fingerprint, $fingerprint)) {
                    throw ValidationException::withMessages(['items' => 'Sesi pembayaran sudah digunakan untuk keranjang berbeda.']);
                }

                return $existing->load('order.items');
            }
            if (MobileCheckout::where('user_id', $customer->id)->where('status', 'reserved')->where('expires_at', '>', now())->exists()) {
                throw ValidationException::withMessages(['items' => 'Selesaikan atau batalkan sesi pembayaran yang masih aktif dahulu.']);
            }
            $service = app(CheckoutService::class);
            $cart = $service->prepare($items);
            if ($expectedTotal !== null && Decimal::units($expectedTotal) !== $cart['total_units']) {
                throw ValidationException::withMessages(['expected_total' => 'Harga berubah. Periksa total sebelum melanjutkan pembayaran.']);
            }
            $service->lockIngredients($cart['requirements']);
            $checkout = MobileCheckout::create(['id' => (string) Str::uuid(), 'user_id' => $customer->id,
                'request_key' => $key, 'fingerprint' => $fingerprint, 'cart' => $cart,
                'total' => Decimal::value($cart['total_units']), 'expires_at' => now()->addMinutes(15)]);
            foreach (array_unique(array_column($cart['items'], 'product_id')) as $productId) {
                DB::table('mobile_checkout_products')->insert(['mobile_checkout_id' => $checkout->id, 'product_id' => $productId]);
            }
            foreach ($cart['requirements'] as $ingredientId => $quantity) {
                DB::table('checkout_reservations')->insert(['mobile_checkout_id' => $checkout->id, 'ingredient_id' => $ingredientId,
                    'quantity' => Decimal::value($quantity), 'expires_at' => $checkout->expires_at]);
            }

            return $checkout;
        }, 3);
    }

    public function cancel(User $user, MobileCheckout $checkout): void
    {
        DB::transaction(function () use ($user, $checkout) {
            User::query()->lockForUpdate()->findOrFail($user->id);
            $locked = MobileCheckout::query()->lockForUpdate()->findOrFail($checkout->id);
            abort_unless($locked->user_id === $user->id && $user->isPengguna(), 403);
            abort_if($locked->order_id !== null, 409, 'Bukti sudah dikirim. Periksa menu Status.');
            $this->lockReservationIngredients($locked);
            ReservedStock::release($locked->id);
            $locked->update(['status' => 'cancelled']);
        }, 3);
    }

    public function lockReservationIngredients(MobileCheckout $checkout): void
    {
        Ingredient::whereIn('id', array_keys($checkout->cart['requirements']))->orderBy('id')->lockForUpdate()->get();
    }
}
