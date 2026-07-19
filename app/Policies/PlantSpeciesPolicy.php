<?php

namespace App\Policies;

use App\Enums\CatalogOrigin;
use App\Models\PlantSpecies;
use App\Models\User;

class PlantSpeciesPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PlantSpecies $species): bool
    {
        if ($species->archived_at !== null) {
            return $user->id === $species->created_by;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, PlantSpecies $species): bool
    {
        return $species->origin === CatalogOrigin::User && $user->id === $species->created_by;
    }

    public function archive(User $user, PlantSpecies $species): bool
    {
        return $species->origin === CatalogOrigin::User
            && $user->id === $species->created_by
            && ! $species->varieties()->exists();
    }

    public function unarchive(User $user, PlantSpecies $species): bool
    {
        return $species->origin === CatalogOrigin::User && $user->id === $species->created_by;
    }

    public function delete(User $user, PlantSpecies $species): bool
    {
        return $species->origin === CatalogOrigin::User
            && $user->id === $species->created_by
            && ! $species->varieties()->exists();
    }
}
