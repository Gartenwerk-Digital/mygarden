<?php

namespace App\Policies;

use App\Models\Garden;
use App\Models\GardenPlanting;
use App\Models\User;

class GardenPlantingPolicy
{
    private function isOwner(User $user, GardenPlanting $planting): bool
    {
        return $user->id === $planting->garden->user_id;
    }

    public function viewAny(User $user, Garden $garden): bool
    {
        return $user->id === $garden->user_id;
    }

    public function create(User $user, Garden $garden): bool
    {
        return $user->id === $garden->user_id;
    }

    public function view(User $user, GardenPlanting $planting): bool
    {
        return $this->isOwner($user, $planting);
    }

    public function update(User $user, GardenPlanting $planting): bool
    {
        return $this->isOwner($user, $planting);
    }

    public function delete(User $user, GardenPlanting $planting): bool
    {
        return $this->isOwner($user, $planting);
    }
}
