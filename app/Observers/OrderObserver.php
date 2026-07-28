<?php

namespace App\Observers;

use App\Jobs\SendOrderStatusPushNotification;
use App\Models\DeviceToken;
use App\Models\Order;

class OrderObserver
{
    public function updated(Order $order): void
    {
        if (! $order->wasChanged('status')) {
            return;
        }

        $status = (string) $order->status;

        if (! in_array($status, ['antrian_baru', 'sedang_dibuat', 'selesai'], true)) {
            return;
        }

        $previousStatus = (string) $order->getOriginal('status');

        DeviceToken::query()
            ->where('user_id', $order->user_id)
            ->pluck('id')
            ->each(function (int $deviceTokenId) use ($order, $previousStatus, $status): void {
                SendOrderStatusPushNotification::dispatch(
                    (int) $order->id,
                    $deviceTokenId,
                    $previousStatus,
                    $status,
                )->afterCommit();
            });
    }
}
