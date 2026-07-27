<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class MobileOrderReceived implements ShouldBroadcast
{
    use Dispatchable;

    /**
     * @var array{id: int, order_number: string, customer_name: string, formatted_total: string, payment_method: string, status: string, created_at: string}
     */
    public array $order;

    public function __construct(Order $order)
    {
        $this->order = [
            'id' => (int) $order->id,
            'order_number' => (string) $order->order_number,
            'customer_name' => (string) $order->customer_name,
            'formatted_total' => (string) $order->formatted_total,
            'payment_method' => $order->payment_method === 'qris' ? 'QRIS' : 'Tunai',
            'status' => (string) $order->status,
            'created_at' => $order->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i'),
        ];
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('karyawan.orders')];
    }

    public function broadcastAs(): string
    {
        return 'mobile-order.created';
    }

    public function broadcastWith(): array
    {
        return ['order' => $this->order];
    }
}