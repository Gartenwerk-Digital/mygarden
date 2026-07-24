<?php

namespace App\Actions\Gardens;

use App\Models\Garden;
use App\Models\User;
use App\Support\ImageStorage;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class ForceDeleteGarden
{
    public function __construct(private readonly ImageStorage $imageStorage) {}

    public function handle(Garden $garden, User $user): void
    {
        if ($user->id !== $garden->user_id) {
            throw new AuthorizationException;
        }

        $paths = $garden->plantings()
            ->withTrashed()
            ->with(['entries.images'])
            ->get()
            ->flatMap(fn ($planting) => $planting->entries->flatMap(
                fn ($entry) => $entry->images->pluck('path')
            ))
            ->all();

        DB::transaction(fn () => $garden->forceDelete());

        $this->imageStorage->deletePaths($paths);
    }
}
