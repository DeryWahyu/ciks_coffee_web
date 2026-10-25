<?php

namespace App\Observers;

use App\Events\OrderQueueUpdated;
use App\Jobs\SendOrderStatusPushNotification;
use App\Models\DeviceToken;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderObserver
{
    public function created(Order $order): void
    {
        $this->updateQueueAfterCommit($order);
    }

    public function updated(Order $order): void
    {
        if ($order->wasChanged(['status', 'cashier_id'])) {
            $this->updateQueueAfterCommit($order);
        }
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
                $orderId = (int) $order->id;
                DB::afterCommit(function () use ($orderId, $deviceTokenId, $previousStatus, $status): void {
                    try {
                        SendOrderStatusPushNotification::dispatch($orderId, $deviceTokenId, $previousStatus, $status);
                    } catch (\Throwable $exception) {
                        // The status is committed; report delivery failure without
                        // telling the cashier that the transaction was rolled back.
                        report($exception);
                    }
                });
            });
    }

    private function updateQueueAfterCommit(Order $order): void
    {
        $id = (int) $order->id;
        $status = (string) $order->status;
        DB::afterCommit(function () use ($id, $status): void {
            try {
                OrderQueueUpdated::dispatch($id, $status);
            } catch (\Throwable $exception) {
                // The order is committed; polling recovers if realtime is unavailable.
                report($exception);
            }
        });
    }
}
