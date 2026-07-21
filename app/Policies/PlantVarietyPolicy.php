<?php

namespace App\Policies;

use App\Enums\CatalogOrigin;
use App\Enums\CatalogStatus;
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
        return $variety->isVisibleTo($user);
    }

    public function create(User $user, PlantSpecies $species): bool
    {
        if ($species->archived_at !== null) {
            return false;
        }

        if ($species->origin === CatalogOrigin::System && $species->catalog_status === CatalogStatus::Approved) {
            return true;
        }

        // Own user-origin species with private or rejected status
        return $species->origin === CatalogOrigin::User
            && $user->id === $species->created_by
            && in_array($species->catalog_status, [CatalogStatus::Private, CatalogStatus::Rejected], strict: true);
    }

    public function update(User $user, PlantVariety $variety): bool
    {
        return $variety->origin === CatalogOrigin::User
            && $user->id === $variety->created_by
            && in_array($variety->catalog_status, [CatalogStatus::Private, CatalogStatus::Rejected], strict: true);
    }

    public function archive(User $user, PlantVariety $variety): bool
    {
        return $variety->origin === CatalogOrigin::User
            && $user->id === $variety->created_by
            && in_array($variety->catalog_status, [CatalogStatus::Private, CatalogStatus::Rejected], strict: true);
    }

    public function unarchive(User $user, PlantVariety $variety): bool
    {
        return $variety->origin === CatalogOrigin::User && $user->id === $variety->created_by;
    }

    public function delete(User $user, PlantVariety $variety): bool
    {
        return $variety->origin === CatalogOrigin::User
            && $user->id === $variety->created_by
            && in_array($variety->catalog_status, [CatalogStatus::Private, CatalogStatus::Rejected], strict: true);
    }

    public function submitForReview(User $user, PlantVariety $variety): bool
    {
        // Variety can only be submitted once parent species is approved
        return $variety->origin === CatalogOrigin::User
            && $user->id === $variety->created_by
            && in_array($variety->catalog_status, [CatalogStatus::Private, CatalogStatus::Rejected], strict: true)
            && $variety->species->catalog_status === CatalogStatus::Approved;
    }

    public function withdrawSubmission(User $user, PlantVariety $variety): bool
    {
        return $variety->origin === CatalogOrigin::User
            && $user->id === $variety->created_by
            && $variety->catalog_status === CatalogStatus::PendingReview;
    }

    public function approve(User $user, PlantVariety $variety): bool
    {
        return $user->canModerateCatalog();
    }

    public function reject(User $user, PlantVariety $variety): bool
    {
        return $user->canModerateCatalog();
    }
}
