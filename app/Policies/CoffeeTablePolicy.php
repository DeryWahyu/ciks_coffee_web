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
     * Only the owner may add a table to a layout.
     */
    public function create(User $user): bool
    {
        return $user->is_active && $user->isPemilik();
    }

    /**
     * Table configuration is intentionally reserved for the owner.
     */
    public function update(User $user, CoffeeTable $coffeeTable): bool
    {
        return $user->is_active && $user->isPemilik();
    }

    /**
     * Permanent deletion is reserved for the owner.
     */
    public function delete(User $user, CoffeeTable $coffeeTable): bool
    {
        return $user->is_active && $user->isPemilik();
    }

    /**
     * Archiving remains reversible and owner-only.
     */
    public function toggleActive(User $user, CoffeeTable $coffeeTable): bool
    {
        return $user->is_active && $user->isPemilik();
    }

    /**
     * Status audit data is operationally sensitive and owner-only.
     */
    public function viewHistory(User $user): bool
    {
        return $user->is_active && $user->isPemilik();
    }

    /**
     * Layout configuration is intentionally reserved for the owner.
     */
    public function manageLayout(User $user, CoffeeTable $coffeeTable): bool
    {
        return $user->is_active && $user->isPemilik();
    }
}
