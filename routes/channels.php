<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, int $id): bool {
    return $user->id === $id;
});

Broadcast::channel('karyawan.orders', function (User $user): bool {
    return $user->is_active && $user->role === User::ROLE_KARYAWAN;
});
