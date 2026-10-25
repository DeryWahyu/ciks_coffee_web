<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShopSetting;
use App\Models\User;
use App\Services\CheckoutService;
use App\Support\Decimal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosController extends Controller
{
    public function paymentSettings()
    {
        $path = ShopSetting::qrisImagePath();

        return response()->json(['image_url' => $path ? asset('storage/'.$path) : null]);
    }

    /**
     * Show the Point of Sales interface.
     */
    public function index(Request $request)
    {
        $categories = Category::where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = Product::with(['category', 'ingredients'])
            ->where('is_active', true)
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get();

        return view('karyawan.pos.index', compact('categories', 'products'));
    }

    /**
     * Get product detail with ingredients (AJAX).
     */
    public function productDetail(Product $product)
    {
        $product->load(['category', 'ingredients']);
        abort_unless($product->is_active && $product->category?->is_active, 404);
        $isCoffee = $product->category->isCoffee();
        $ingredientsData = [];

        if ($isCoffee) {
            $ingredientsData['base'] = $product->ingredientsByVariant('base')->map(fn ($i) => [
                'id' => $i->id,
                'nama_bahan' => $i->nama_bahan,
                'satuan' => $i->satuan,
                'quantity' => $i->pivot->quantity,
                'stok' => $i->stok,
            ]);
            $ingredientsData['lite'] = $product->ingredientsByVariant('lite')->map(fn ($i) => [
                'id' => $i->id,
                'nama_bahan' => $i->nama_bahan,
                'satuan' => $i->satuan,
                'quantity' => $i->pivot->quantity,
                'stok' => $i->stok,
            ]);
        } else {
            $ingredientsData['default'] = $product->ingredientsByVariant(null)->map(fn ($i) => [
                'id' => $i->id,
                'nama_bahan' => $i->nama_bahan,
                'satuan' => $i->satuan,
                'quantity' => $i->pivot->quantity,
                'stok' => $i->stok,
            ]);
        }

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'image' => $product->image ? asset('storage/'.$product->image) : null,
            'price' => $product->price,
            'price_lite' => $product->price_lite,
            'formatted_price' => $product->formatted_price,
            'formatted_price_lite' => $product->formatted_price_lite,
            'category' => $product->category->name,
            'is_coffee' => $isCoffee,
            'ingredients' => $ingredientsData,
        ]);
    }

    /**
     * Process order / checkout.
     */
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'payment_method' => 'required|in:cash,qris',
            'cash_received' => 'required_if:payment_method,cash|nullable|numeric|decimal:0,2|min:0|max:9999999999.99',
            'expected_total' => 'nullable|numeric|decimal:0,2|min:0|max:9999999999.99',
            'idempotency_key' => 'nullable|uuid',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variant' => 'nullable|in:base,lite',
            'items.*.quantity' => 'required|integer|min:1|max:2147483647',
        ], [
            'customer_name.required' => 'Nama pelanggan wajib diisi.',
            'items.required' => 'Pesanan tidak boleh kosong.',
        ]);

        $cashier = $request->user();

        $service = app(CheckoutService::class);
        $fingerprint = $this->checkoutFingerprint($validated);
        $order = DB::transaction(function () use ($validated, $cashier, $service, $fingerprint) {
            // Serialize retries by this cashier before checking stock or prices.
            $lockedCashier = User::query()->lockForUpdate()->findOrFail($cashier->id);
            abort_unless($lockedCashier->is_active && $lockedCashier->isKaryawan(), 403);
            $key = $validated['idempotency_key'] ?? null;
            if ($key) {
                $existing = Order::where('user_id', $cashier->id)->where('checkout_key', $key)->first();
                if ($existing) {
                    if (! hash_equals((string) $existing->checkout_fingerprint, $fingerprint)) {
                        throw ValidationException::withMessages(['idempotency_key' => 'Identitas pembayaran ini sudah digunakan untuk pesanan berbeda.']);
                    }

                    return $existing;
                }
            }

            $cart = $service->prepare($validated['items']);
            $total = $cart['total_units'];
            if (isset($validated['expected_total']) && Decimal::units($validated['expected_total']) !== $total) {
                throw ValidationException::withMessages(['expected_total' => 'Harga produk berubah. Periksa kembali keranjang sebelum melakukan pembayaran.']);
            }
            $cash = $validated['payment_method'] === 'cash' ? Decimal::units($validated['cash_received']) : null;
            if ($cash !== null && $cash < $total) {
                throw ValidationException::withMessages(['cash_received' => 'Uang diterima kurang dari total belanja.']);
            }
            $ingredients = $service->lockIngredients($cart['requirements']);
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'customer_name' => $validated['customer_name'],
                'user_id' => $cashier->id, 'cashier_id' => $cashier->id,
                'payment_method' => $validated['payment_method'],
                'total' => Decimal::value($total),
                'cash_received' => $cash !== null ? Decimal::value($cash) : null,
                'change_amount' => $cash !== null ? Decimal::value($cash - $total) : null,
                'status' => 'antrian_baru', 'paid_at' => now(),
                'checkout_key' => $key, 'checkout_fingerprint' => $key ? $fingerprint : null,
            ]);
            $service->storeItems($order, $cart['items']);
            $service->deduct($ingredients, $cart['requirements']);

            return $order;
        }, 3);

        $order->load('items');

        return response()->json([
            'success' => true,
            'message' => 'Pesanan berhasil diproses.',
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'payment_method' => $order->payment_method,
                'total' => $order->total,
                'formatted_total' => $order->formatted_total,
                'cash_received' => $order->cash_received,
                'change_amount' => $order->change_amount,
                'status' => $order->status,
                'status_label' => $order->status_label,
                'paid_at' => $order->paid_at->format('d/m/Y H:i'),
                'cashier' => $cashier->name,
                'items' => $order->items->map(fn ($item) => [
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $item->subtotal,
                ]),
            ],
        ]);
    }

    private function checkoutFingerprint(array $validated): string
    {
        $items = array_map(fn ($item) => [
            'product_id' => (int) $item['product_id'],
            'variant' => $item['variant'] ?? null, 'quantity' => (int) $item['quantity'],
        ], $validated['items']);
        usort($items, fn ($a, $b) => [$a['product_id'], $a['variant'], $a['quantity']] <=> [$b['product_id'], $b['variant'], $b['quantity']]);

        return hash('sha256', json_encode([
            'customer' => $validated['customer_name'], 'payment' => $validated['payment_method'],
            'cash' => $validated['payment_method'] === 'cash' ? Decimal::units($validated['cash_received']) : null,
            'expected_total' => isset($validated['expected_total']) ? Decimal::units($validated['expected_total']) : null,
            'items' => $items,
        ], JSON_THROW_ON_ERROR));
    }
}
