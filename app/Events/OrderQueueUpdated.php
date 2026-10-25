<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class OrderQueueUpdated implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(public int $orderId, public string $status) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('karyawan.orders')];
    }

    public function broadcastAs(): string
    {
        return 'order-queue.updated';
    }

    public function broadcastWith(): array
    {
        return ['order_id' => $this->orderId, 'status' => $this->status];
    }
}
