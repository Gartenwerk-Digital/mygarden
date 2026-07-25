<?php

namespace App\Actions\Gardens;

use App\Models\Garden;
use App\Models\GardenImage;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SetGardenCoverImage
{
    public function handle(User $user, Garden $garden, GardenImage $image): void
    {
        Gate::forUser($user)->authorize('update', $garden);

        if ($garden->trashed()) {
            throw new AuthorizationException;
        }

        if ($image->garden_id !== $garden->id) {
            throw new AuthorizationException;
        }

        DB::transaction(function () use ($garden, $image): void {
            /** @var Garden $locked */
            $locked = Garden::query()->whereKey($garden->id)->lockForUpdate()->firstOrFail();

            $locked->images()->where('is_cover', true)->update(['is_cover' => null]);
            $image->forceFill(['is_cover' => true])->save();
        });
    }
}
