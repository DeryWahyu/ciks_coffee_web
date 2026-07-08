<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosController extends Controller
{
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
        $isCoffee = $product->category->isCoffee();
        $ingredientsData = [];

        if ($isCoffee) {
            $ingredientsData['base'] = $product->ingredientsByVariant('base')->map(fn($i) => [
                'nama_bahan' => $i->nama_bahan,
                'satuan' => $i->satuan,
                'quantity' => $i->pivot->quantity,
                'stok' => $i->stok,
            ]);
            $ingredientsData['lite'] = $product->ingredientsByVariant('lite')->map(fn($i) => [
                'nama_bahan' => $i->nama_bahan,
                'satuan' => $i->satuan,
                'quantity' => $i->pivot->quantity,
                'stok' => $i->stok,
            ]);
        } else {
            $ingredientsData['default'] = $product->ingredientsByVariant(null)->map(fn($i) => [
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
            'image' => $product->image ? asset('storage/' . $product->image) : null,
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
            'cash_received' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variant' => 'nullable|in:base,lite',
            'items.*.quantity' => 'required|integer|min:1',
        ], [
            'customer_name.required' => 'Nama pelanggan wajib diisi.',
            'items.required' => 'Pesanan tidak boleh kosong.',
        ]);

        $order = DB::transaction(function () use ($validated) {
            $total = 0;
            $orderItems = [];

            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                $variantLabel = $item['variant'] ?? null;
                $unitPrice = ($variantLabel === 'lite' && $product->price_lite !== null) ? $product->price_lite : $product->price;
                $subtotal = $unitPrice * $item['quantity'];
                $total += $subtotal;

                $productName = $product->name;
                if ($variantLabel) {
                    $productName .= ' (' . ucfirst($variantLabel) . ')';
                }

                $orderItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $productName,
                    'variant' => $variantLabel,
                    'quantity' => $item['quantity'],
                    'price' => $unitPrice,
                    'subtotal' => $subtotal,
                ];
            }

            if ($validated['payment_method'] === 'cash') {
                $cashReceived = $validated['cash_received'] ?? $total;
                if ($cashReceived < $total) {
                    throw ValidationException::withMessages([
                        'cash_received' => 'Uang diterima kurang dari total belanja (Rp ' . number_format($total, 0, ',', '.') . ').'
                    ]);
                }
            } else {
                $cashReceived = null;
            }

            $changeAmount = $cashReceived ? max(0, $cashReceived - $total) : null;

            // Validate Stock BEFORE creating order
            foreach ($validated['items'] as $item) {
                $product = Product::with('ingredients')->find($item['product_id']);
                $variant = $item['variant'] ?? null;
                $ingredients = $product->ingredientsByVariant($variant);

                foreach ($ingredients as $ingredient) {
                    $required = $ingredient->pivot->quantity * $item['quantity'];
                    if ($ingredient->stok < $required) {
                        throw ValidationException::withMessages([
                            'items' => "Stok bahan {$ingredient->nama_bahan} tidak cukup untuk produk {$product->name}."
                        ]);
                    }
                }
            }

            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'customer_name' => $validated['customer_name'],
                'user_id' => Auth::id(),
                'cashier_id' => Auth::id(),
                'payment_method' => $validated['payment_method'],
                'total' => $total,
                'cash_received' => $cashReceived,
                'change_amount' => $changeAmount,
                'status' => 'antrian_baru',
                'paid_at' => now(),
            ]);

            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }

            // Deduct ingredient stock
            foreach ($validated['items'] as $item) {
                $product = Product::with('ingredients')->find($item['product_id']);
                $variant = $item['variant'] ?? null;
                $ingredients = $product->ingredientsByVariant($variant);

                foreach ($ingredients as $ingredient) {
                    $deduction = $ingredient->pivot->quantity * $item['quantity'];
                    $ingredient->decrement('stok', $deduction);
                }
            }

            return $order;
        }, 3);

        $order->load('items');

        return response()->json([
            'success' => true,
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
                'cashier' => Auth::user()->name,
                'items' => $order->items->map(fn($i) => [
                    'product_name' => $i->product_name,
                    'quantity' => $i->quantity,
                    'price' => $i->price,
                    'subtotal' => $i->subtotal,
                ]),
            ],
        ]);
    }
}
