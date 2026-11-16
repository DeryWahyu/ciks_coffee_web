<?php

namespace App\Policies;

use App\Models\CoffeeTable;
use App\Models\User;

class CoffeeTablePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, CoffeeTable $coffeeTable): bool
    {
        return $user->is_active;
    }

    /**
     * Pemilik and karyawan may change an operational table status.
     */
    public function updateStatus(User $user, CoffeeTable $coffeeTable): bool
    {
        return $user->is_active && ($user->isPemilik() || $user->isKaryawan());
    }

    /**
     * Active owners and employees may use the table editor.
     */
    public function create(User $user): bool
    {
        return $user->is_active && ($user->isPemilik() || $user->isKaryawan());
    }

    /**
     * Table configuration uses the same staff permissions as the editor.
     */
    public function update(User $user, CoffeeTable $coffeeTable): bool
    {
        return $user->is_active && ($user->isPemilik() || $user->isKaryawan());
    }

    /**
     * Staff may delete tables that have no operational history.
     */
    public function delete(User $user, CoffeeTable $coffeeTable): bool
    {
        return $user->is_active && ($user->isPemilik() || $user->isKaryawan());
    }

    /**
     * Staff may archive or restore tables without losing history.
     */
    public function toggleActive(User $user, CoffeeTable $coffeeTable): bool
    {
        return $user->is_active && ($user->isPemilik() || $user->isKaryawan());
    }

    /**
     * Status audit data is operationally sensitive and owner-only.
     */
    public function viewHistory(User $user): bool
    {
        return $user->is_active && $user->isPemilik();
    }

    /**
     * Active owners and employees may configure table positions.
     */
    public function manageLayout(User $user, CoffeeTable $coffeeTable): bool
    {
        return $user->is_active && ($user->isPemilik() || $user->isKaryawan());
    }
}
