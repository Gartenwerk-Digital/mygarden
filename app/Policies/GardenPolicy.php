<?php

namespace App\Policies;

use App\Models\Garden;
use App\Models\User;

class GardenPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Phase 3: Nur Eigentümer. Community-Freigabe folgt in einer späteren Phase.
     */
    public function view(User $user, Garden $garden): bool
    {
        return $user->id === $garden->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Garden $garden): bool
    {
        return $user->id === $garden->user_id;
    }

    public function archive(User $user, Garden $garden): bool
    {
        return $user->id === $garden->user_id;
    }

    public function unarchive(User $user, Garden $garden): bool
    {
        return $user->id === $garden->user_id;
    }

    public function delete(User $user, Garden $garden): bool
    {
        return $user->id === $garden->user_id;
    }
}
