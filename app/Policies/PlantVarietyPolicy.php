<?php

namespace App\Policies;

use App\Enums\CatalogOrigin;
use App\Models\PlantSpecies;
use App\Models\PlantVariety;
use App\Models\User;

class PlantVarietyPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PlantVariety $variety): bool
    {
        if ($variety->archived_at !== null) {
            return $user->id === $variety->created_by;
        }

        return true;
    }

    public function create(User $user, PlantSpecies $species): bool
    {
        if ($species->archived_at !== null) {
            return false;
        }

        if ($species->origin === CatalogOrigin::System) {
            return true;
        }

        return $user->id === $species->created_by;
    }

    public function update(User $user, PlantVariety $variety): bool
    {
        return $variety->origin === CatalogOrigin::User && $user->id === $variety->created_by;
    }

    public function archive(User $user, PlantVariety $variety): bool
    {
        return $variety->origin === CatalogOrigin::User && $user->id === $variety->created_by;
    }

    public function unarchive(User $user, PlantVariety $variety): bool
    {
        return $variety->origin === CatalogOrigin::User && $user->id === $variety->created_by;
    }

    public function delete(User $user, PlantVariety $variety): bool
    {
        return $variety->origin === CatalogOrigin::User && $user->id === $variety->created_by;
    }
}
