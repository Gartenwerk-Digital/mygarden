<?php

namespace App\Actions\Gardens;

use App\Models\Garden;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UnsetGardenCoverImage
{
    public function handle(User $user, Garden $garden): void
    {
        Gate::forUser($user)->authorize('update', $garden);

        if ($garden->trashed()) {
            throw new AuthorizationException;
        }

        DB::transaction(function () use ($garden): void {
            /** @var Garden $locked */
            $locked = Garden::query()->whereKey($garden->id)->lockForUpdate()->firstOrFail();

            $locked->images()->where('is_cover', true)->update(['is_cover' => null]);
        });
    }
}
