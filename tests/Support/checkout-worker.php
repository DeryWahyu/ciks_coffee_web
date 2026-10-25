<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(ConsoleKernel::class)->bootstrap();
if (! $app->environment('testing') || ! str_starts_with(config('database.connections.mysql.database'), 'cikscoffee_audit_')) {
    throw new RuntimeException('Checkout workers require an isolated audit database.');
}
[$script, $userId, $path, $encoded, $barrier, $workerId, $method] = $argv;
Auth::guard('web')->setUser(User::findOrFail($userId));
Auth::shouldUse('web');
file_put_contents($barrier.'/ready-'.$workerId, 'ready');
$deadline = microtime(true) + 20;
while (! is_file($barrier.'/go')) {
    if (microtime(true) > $deadline) {
        throw new RuntimeException('Checkout barrier timed out.');
    }
    usleep(10000);
}
$request = Request::create($path, $method, [], [], [], [
    'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
], base64_decode($encoded, true));
$kernel = $app->make(Kernel::class);
$response = $kernel->handle($request);
echo json_encode(['status' => $response->getStatusCode(), 'body' => json_decode($response->getContent(), true),
    'flash_success' => $app['session.store']->get('success'), 'flash_error' => $app['session.store']->get('error'),
], JSON_THROW_ON_ERROR);
$kernel->terminate($request, $response);
