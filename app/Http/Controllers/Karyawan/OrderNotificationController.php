<?php

namespace App\Http\Controllers\Karyawan;

use App\Events\MobileOrderReceived;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderNotificationController extends Controller
{
    /** Re-scan waiting orders on every pass, including commits missed by realtime. */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate(['after_id' => 'nullable|integer|min:0']);
        $orders = Order::query()
            ->where('id', '>', $validated['after_id'] ?? 0)
            ->where('status', 'menunggu_verifikasi')
            ->where('payment_method', 'qris')
            ->whereHas('user', fn ($users) => $users->where('role', User::ROLE_PENGGUNA))
            ->orderBy('id')->limit(51)->get();
        $hasMore = $orders->count() > 50;
        $page = $orders->take(50)->values();

        return response()->json([
            'orders' => $page->map(fn (Order $order) => (new MobileOrderReceived($order))->order),
            'next_cursor' => $hasMore ? $page->last()->id : null,
        ])->header('Cache-Control', 'private, no-store');
    }
}
