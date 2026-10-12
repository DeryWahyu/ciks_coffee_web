<?php

require '/var/www/cikscoffee/vendor/autoload.php';
$app = require '/var/www/cikscoffee/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$email = getenv('CIKS_OWNER_EMAIL');
$password = getenv('CIKS_OWNER_PASSWORD');
if (! $email || ! $password) {
    throw new RuntimeException('Owner credentials must be supplied through environment variables.');
}

$owner = App\Models\User::firstOrCreate(
    ['email' => $email],
    [
        'name' => 'Pemilik Ciks Coffee',
        'password' => Illuminate\Support\Facades\Hash::make($password),
        'role' => App\Models\User::ROLE_PEMILIK,
        'is_active' => true,
    ],
);
echo 'OWNER_READY role='.$owner->role.' email='.$owner->email.PHP_EOL;
