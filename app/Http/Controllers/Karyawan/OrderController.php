<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Show order queue page.
     */
    public function index(Request $request)
    {
        $status = $request->get('status');

        $query = Order::with('items')->latest();

        if ($status && in_array($status, ['antrian_baru', 'sedang_dibuat', 'selesai'])) {
            $query->where('status', $status);
        }

        // Only show today's orders by default
        if (!$request->filled('all')) {
            $query->whereDate('created_at', today());
        }

        $orders = $query->paginate(15)->withQueryString();

        $counts = [
            'antrian_baru' => Order::whereDate('created_at', today())->where('status', 'antrian_baru')->count(),
            'sedang_dibuat' => Order::whereDate('created_at', today())->where('status', 'sedang_dibuat')->count(),
            'selesai' => Order::whereDate('created_at', today())->where('status', 'selesai')->count(),
        ];

        return view('karyawan.orders.index', compact('orders', 'counts', 'status'));
    }

    /**
     * Update order status.
     */
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:antrian_baru,sedang_dibuat,selesai',
        ]);

        $order->update(['status' => $validated['status']]);

        return back()->with('success', "Pesanan #{$order->order_number} diperbarui ke \"{$order->status_label}\".");
    }

    /**
     * Get order detail for receipt (AJAX).
     */
    public function receipt(Order $order)
    {
        $order->load('items', 'user');

        return response()->json([
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'payment_method' => $order->payment_method,
            'total' => $order->total,
            'formatted_total' => $order->formatted_total,
            'cash_received' => $order->cash_received,
            'change_amount' => $order->change_amount,
            'status_label' => $order->status_label,
            'paid_at' => $order->paid_at?->format('d/m/Y H:i'),
            'cashier' => $order->user->name,
            'items' => $order->items->map(fn($i) => [
                'product_name' => $i->product_name,
                'quantity' => $i->quantity,
                'price' => $i->price,
                'subtotal' => $i->subtotal,
            ]),
        ]);
    }
}
