<?php

namespace App\Policies;

use App\Models\GardenPlanting;
use App\Models\PlantingEntry;
use App\Models\User;

class PlantingEntryPolicy
{
    public function viewAny(User $user, GardenPlanting $planting): bool
    {
        return $user->id === $planting->garden->user_id;
    }

    public function create(User $user, GardenPlanting $planting): bool
    {
        return $user->id === $planting->garden->user_id;
    }

    public function view(User $user, PlantingEntry $entry): bool
    {
        return $user->id === $entry->planting->garden->user_id;
    }

    public function update(User $user, PlantingEntry $entry): bool
    {
        return $user->id === $entry->planting->garden->user_id;
    }

    public function delete(User $user, PlantingEntry $entry): bool
    {
        return $user->id === $entry->planting->garden->user_id;
    }
}
