<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
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
