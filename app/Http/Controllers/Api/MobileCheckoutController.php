<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MobileCheckout;
use App\Services\MobileCheckoutService;
use Illuminate\Http\Request;

class MobileCheckoutController extends Controller
{
    public function store(Request $request, MobileCheckoutService $service)
    {
        abort_unless($request->user()->isPengguna(), 403);
        $data = $request->validate(['request_key' => 'required|uuid', 'expected_total' => 'nullable|numeric|decimal:0,2|min:0|max:9999999999.99',
            'items' => 'required|array|min:1|max:100', 'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.variant' => 'nullable|in:base,lite', 'items.*.quantity' => 'required|integer|min:1|max:2147483647']);

        return response()->json(['success' => true, 'data' => self::present($service->reserve($request->user(), $data['items'], $data['request_key'], $data['expected_total'] ?? null))], 201);
    }

    public function current(Request $request)
    {
        abort_unless($request->user()->isPengguna(), 403);
        $checkout = MobileCheckout::where('user_id', $request->user()->id)->where('status', 'reserved')->latest()->first();

        return response()->json(['success' => true, 'data' => $checkout ? self::present($checkout) : null]);
    }

    public function destroy(Request $request, MobileCheckout $checkout, MobileCheckoutService $service)
    {
        $service->cancel($request->user(), $checkout);

        return response()->json(['success' => true]);
    }

    public static function present(MobileCheckout $checkout): array
    {
        return ['id' => $checkout->id, 'request_key' => $checkout->request_key, 'total' => $checkout->total,
            'can_pay' => $checkout->status === 'reserved' && $checkout->expires_at->isFuture(),
            'expires_at' => $checkout->expires_at->toIso8601String(), 'status' => $checkout->status,
            'items' => array_map(fn ($item) => array_diff_key($item, ['recipe' => true]), $checkout->cart['items']),
            'order' => $checkout->order ? app(OrderController::class)->formatOrder($checkout->order->load('items')) : null];
    }
}
