<?php

namespace App\Policies;

use App\Models\FloorLayout;
use App\Models\User;

class FloorLayoutPolicy
{
    /**
     * Any active account may read a layout made available to its role.
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, FloorLayout $floorLayout): bool
    {
        return $user->is_active;
    }

    /**
     * Only the owner may configure layout structure and positions.
     */
    public function manage(User $user, FloorLayout $floorLayout): bool
    {
        return $user->is_active && $user->isPemilik();
    }
}
