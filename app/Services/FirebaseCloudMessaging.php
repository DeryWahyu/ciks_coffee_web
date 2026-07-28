<?php

namespace App\Services;

use App\Exceptions\FcmDeliveryException;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FirebaseCloudMessaging
{
    private const MESSAGING_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public function sendToDevice(
        string $deviceToken,
        string $title,
        string $body,
        array $data,
        int $orderId,
    ): void {
        $response = $this->sendRequest(
            $this->accessToken(),
            $deviceToken,
            $title,
            $body,
            $data,
            $orderId,
        );

        if ($response->status() === 401) {
            Cache::forget($this->accessTokenCacheKey());
            $response = $this->sendRequest(
                $this->accessToken(),
                $deviceToken,
                $title,
                $body,
                $data,
                $orderId,
            );
        }

        if ($response->failed()) {
            throw FcmDeliveryException::fromResponse($response);
        }
    }

    private function sendRequest(
        string $accessToken,
        string $deviceToken,
        string $title,
        string $body,
        array $data,
        int $orderId,
    ): Response {
        $projectId = (string) config('services.firebase.project_id');

        if ($projectId === '') {
            throw new RuntimeException('FIREBASE_PROJECT_ID belum dikonfigurasi.');
        }

        return Http::asJson()
            ->acceptJson()
            ->withToken($accessToken)
            ->connectTimeout(10)
            ->timeout(20)
            ->post(
                "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
                [
                    'message' => [
                        'token' => $deviceToken,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'data' => collect($data)
                            ->mapWithKeys(fn ($value, $key) => [(string) $key => (string) $value])
                            ->all(),
                        'android' => [
                            'priority' => 'high',
                            'collapse_key' => "order_{$orderId}",
                            'ttl' => '86400s',
                            'notification' => [
                                'channel_id' => 'order_status_updates',
                                'sound' => 'default',
                                'tag' => "order_{$orderId}",
                            ],
                        ],
                    ],
                ],
            );
    }

    private function accessToken(): string
    {
        return Cache::remember(
            $this->accessTokenCacheKey(),
            now()->addMinutes(50),
            function (): string {
                $credentialsPath = (string) config('services.firebase.credentials');

                if (
                    $credentialsPath === ''
                    || ! is_file($credentialsPath)
                    || ! is_readable($credentialsPath)
                ) {
                    throw new RuntimeException('Credential Firebase tidak tersedia atau tidak dapat dibaca.');
                }

                $credentials = new ServiceAccountCredentials(
                    [self::MESSAGING_SCOPE],
                    $credentialsPath,
                );
                $authToken = $credentials->fetchAuthToken();
                $accessToken = $authToken['access_token'] ?? null;

                if (! is_string($accessToken) || $accessToken === '') {
                    throw new RuntimeException('Google tidak mengembalikan access token Firebase.');
                }

                return $accessToken;
            },
        );
    }

    private function accessTokenCacheKey(): string
    {
        return 'firebase.messaging.access_token.'
            .sha1((string) config('services.firebase.credentials'));
    }
}
