<?php

namespace App\Jobs;

use App\Exceptions\FcmDeliveryException;
use App\Models\DeviceToken;
use App\Models\Order;
use App\Services\FirebaseCloudMessaging;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendOrderStatusPushNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [10, 60, 180];

    public function __construct(
        public readonly int $orderId,
        public readonly int $deviceTokenId,
        public readonly string $previousStatus,
        public readonly string $status,
    ) {
        $this->onQueue('default');
    }

    public function handle(FirebaseCloudMessaging $messaging): void
    {
        $order = Order::query()->find($this->orderId);
        $deviceToken = DeviceToken::query()->find($this->deviceTokenId);

        if (! $order || ! $deviceToken || $deviceToken->user_id !== $order->user_id) {
            return;
        }

        $copy = $this->notificationCopy($order);

        if ($copy === null) {
            return;
        }

        try {
            $messaging->sendToDevice(
                $deviceToken->token,
                $copy['title'],
                $copy['body'],
                [
                    'type' => 'order_status',
                    'order_id' => (string) $order->id,
                    'order_number' => $order->order_number,
                    'status' => $this->status,
                    'status_label' => $order->status_label,
                ],
                $order->id,
            );
        } catch (FcmDeliveryException $exception) {
            if ($exception->invalidDeviceToken) {
                $deviceToken->delete();

                return;
            }

            throw $exception;
        }
    }

    /**
     * @return array{title: string, body: string}|null
     */
    private function notificationCopy(Order $order): ?array
    {
        return match ($this->status) {
            'antrian_baru' => [
                'title' => 'Pembayaran terverifikasi',
                'body' => "Pesanan {$order->order_number} sudah masuk antrean.",
            ],
            'sedang_dibuat' => [
                'title' => 'Pesanan sedang dibuat',
                'body' => "Barista sedang menyiapkan pesanan {$order->order_number}.",
            ],
            'selesai' => [
                'title' => 'Pesanan siap diambil',
                'body' => "Pesanan {$order->order_number} sudah siap. Silakan ambil di kasir.",
            ],
            default => null,
        };
    }
}
