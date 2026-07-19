<?php

namespace App\Policies;

use App\Enums\CatalogOrigin;
use App\Enums\CatalogStatus;
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
        return $species->isVisibleTo($user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, PlantSpecies $species): bool
    {
        return $species->origin === CatalogOrigin::User
            && $user->id === $species->created_by
            && in_array($species->catalog_status, [CatalogStatus::Private, CatalogStatus::Rejected], strict: true);
    }

    public function archive(User $user, PlantSpecies $species): bool
    {
        return $species->origin === CatalogOrigin::User
            && $user->id === $species->created_by
            && in_array($species->catalog_status, [CatalogStatus::Private, CatalogStatus::Rejected], strict: true);
    }

    public function unarchive(User $user, PlantSpecies $species): bool
    {
        return $species->origin === CatalogOrigin::User && $user->id === $species->created_by;
    }

    public function delete(User $user, PlantSpecies $species): bool
    {
        return $species->origin === CatalogOrigin::User
            && $user->id === $species->created_by
            && in_array($species->catalog_status, [CatalogStatus::Private, CatalogStatus::Rejected], strict: true);
    }

    public function submitForReview(User $user, PlantSpecies $species): bool
    {
        return $species->origin === CatalogOrigin::User
            && $user->id === $species->created_by
            && in_array($species->catalog_status, [CatalogStatus::Private, CatalogStatus::Rejected], strict: true);
    }

    public function withdrawSubmission(User $user, PlantSpecies $species): bool
    {
        return $species->origin === CatalogOrigin::User
            && $user->id === $species->created_by
            && $species->catalog_status === CatalogStatus::PendingReview;
    }
}
