<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    // Test requests represent the published mobile checkout protocol.
    protected $defaultHeaders = ['X-Ciks-Checkout-Version' => '2'];

    /** Exercise the public reserve-before-payment contract, including failed preflight responses. */
    protected function postMobileOrder(array $payload): TestResponse
    {
        if (! isset($payload['checkout_id'])) {
            $quote = $this->postJson('/api/checkouts', [
                'request_key' => (string) Str::uuid(),
                'items' => $payload['items'] ?? [],
            ]);
            if ($quote->status() !== 201) {
                return $quote;
            }
            $payload['checkout_id'] = $quote->json('data.id');
        }

        return $this->postJson('/api/orders', $payload);
    }

    public function createApplication(): Application
    {
        $app = parent::createApplication();
        if (! $app->environment('testing')) {
            throw new \LogicException('Tests require APP_ENV=testing.');
        }
        $connection = $app['db']->connection();
        $database = $connection->getDatabaseName();
        $safe = $connection->getDriverName() === 'sqlite'
            ? $database === ':memory:'
            : str_starts_with($database, 'cikscoffee_audit_');
        if (! $safe) {
            throw new \LogicException('Refusing to run tests outside SQLite memory or a cikscoffee_audit_ database.');
        }

        return $app;
    }
}
