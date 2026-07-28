<?php

namespace App\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;

class FcmDeliveryException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly bool $invalidDeviceToken = false,
    ) {
        parent::__construct($message);
    }

    public static function fromResponse(Response $response): self
    {
        $payload = $response->json();
        $status = data_get($payload, 'error.status', 'UNKNOWN');
        $errorCodes = collect(data_get($payload, 'error.details', []))
            ->pluck('errorCode')
            ->filter()
            ->all();

        return new self(
            "FCM menolak pesan dengan HTTP {$response->status()} ({$status}).",
            count(array_intersect(
                $errorCodes,
                ['UNREGISTERED', 'SENDER_ID_MISMATCH'],
            )) > 0,
        );
    }
}
