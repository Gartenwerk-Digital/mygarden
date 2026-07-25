<?php

namespace App\Actions\Gardens;

use App\Models\Garden;
use App\Models\GardenImage;
use App\Models\User;
use App\Support\ImageStorage;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DeleteGardenImage
{
    public function __construct(private readonly ImageStorage $imageStorage) {}

    public function handle(User $user, Garden $garden, GardenImage $image): void
    {
        Gate::forUser($user)->authorize('update', $garden);

        if ($garden->trashed()) {
            throw new AuthorizationException;
        }

        if ($image->garden_id !== $garden->id) {
            throw new AuthorizationException;
        }

        $path = $image->path;

        DB::transaction(function () use ($image): void {
            $image->delete();
        });

        $this->imageStorage->deletePaths([$path]);
    }
}
