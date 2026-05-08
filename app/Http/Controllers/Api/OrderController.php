<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShopSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Create a new order from mobile app (customer checkout).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:cash,qris',
            'cash_received'  => 'nullable|numeric|min:0',
            'items'          => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variant'    => 'nullable|in:base,lite',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.price'      => 'required|numeric|min:0',
        ], [
            'items.required' => 'Pesanan tidak boleh kosong.',
        ]);

        $order = DB::transaction(function () use ($validated) {
            $total = 0;
            $orderItems = [];

            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                $subtotal = $item['price'] * $item['quantity'];
                $total += $subtotal;

                $variantLabel = $item['variant'] ?? null;
                $productName = $product->name;
                if ($variantLabel) {
                    $productName .= ' (' . ucfirst($variantLabel) . ')';
                }

                $orderItems[] = [
                    'product_id'   => $product->id,
                    'product_name' => $productName,
                    'variant'      => $variantLabel,
                    'quantity'     => $item['quantity'],
                    'price'        => $item['price'],
                    'subtotal'     => $subtotal,
                ];
            }

            $cashReceived = $validated['payment_method'] === 'cash'
                ? ($validated['cash_received'] ?? $total)
                : null;
            $changeAmount = $cashReceived ? max(0, $cashReceived - $total) : null;

            // customer_name is taken from the authenticated user
            $order = Order::create([
                'order_number'   => Order::generateOrderNumber(),
                'customer_name'  => Auth::user()->name,
                'user_id'        => Auth::id(),
                'payment_method' => $validated['payment_method'],
                'total'          => $total,
                'cash_received'  => $cashReceived,
                'change_amount'  => $changeAmount,
                'status'         => 'antrian_baru',
                'paid_at'        => now(),
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
        });

        $order->load('items');

        return response()->json([
            'success' => true,
            'order' => $this->formatOrder($order),
        ], 201);
    }

    /**
     * Get all orders for the logged-in customer (grouped by status for tracking).
     */
    public function active(Request $request)
    {
        $orders = Order::with('items')
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['antrian_baru', 'sedang_dibuat', 'selesai'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders->map(fn($order) => $this->formatOrder($order)),
        ]);
    }

    /**
     * Get order history (diambil = picked up) for the logged-in customer.
     */
    public function history(Request $request)
    {
        $orders = Order::with('items')
            ->where('user_id', $request->user()->id)
            ->where('status', 'diambil')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders->map(fn($order) => $this->formatOrder($order)),
        ]);
    }

    /**
     * Confirm order pickup (customer marks as "diambil").
     */
    public function confirmPickup(Request $request, Order $order)
    {
        // Ensure the order belongs to the authenticated user
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Only allow pickup confirmation for 'selesai' orders
        if ($order->status !== 'selesai') {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan belum selesai diproses.'
            ], 422);
        }

        $order->update(['status' => 'diambil']);

        return response()->json([
            'success' => true,
            'message' => 'Pesanan telah dikonfirmasi diambil.',
        ]);
    }

    /**
     * Get QRIS image URL for the shop.
     */
    public function qrisImage()
    {
        $qrisPath = ShopSetting::getValue('qris_image');

        if (!$qrisPath) {
            return response()->json([
                'success' => false,
                'message' => 'QRIS belum tersedia.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'image_url' => 'storage/' . $qrisPath,
            ],
        ]);
    }

    /**
     * Format order for JSON response.
     */
    private function formatOrder(Order $order): array
    {
        return [
            'id'              => $order->id,
            'order_number'    => $order->order_number,
            'customer_name'   => $order->customer_name,
            'payment_method'  => $order->payment_method,
            'total'           => $order->total,
            'formatted_total' => $order->formatted_total,
            'cash_received'   => $order->cash_received,
            'change_amount'   => $order->change_amount,
            'status'          => $order->status,
            'status_label'    => $order->status_label,
            'paid_at'         => $order->paid_at?->format('d/m/Y H:i'),
            'created_at'      => $order->created_at->toIso8601String(),
            'items'           => $order->items->map(fn($i) => [
                'product_name' => $i->product_name,
                'variant'      => $i->variant,
                'quantity'     => $i->quantity,
                'price'        => $i->price,
                'subtotal'     => $i->subtotal,
            ])->toArray(),
        ];
    }
}
