<?php

namespace App\Actions\PlantingEntries;

use App\Models\GardenPlanting;
use App\Models\User;
use App\Support\ImageStorage;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class ForceDeleteGardenPlanting
{
    public function __construct(private readonly ImageStorage $imageStorage) {}

    public function handle(GardenPlanting $planting, User $user): void
    {
        if ($user->id !== $planting->garden->user_id) {
            throw new AuthorizationException;
        }

        $paths = $planting->entries()
            ->with('images')
            ->get()
            ->flatMap(fn ($entry) => $entry->images->pluck('path'))
            ->all();

        DB::transaction(fn () => $planting->forceDelete());

        $this->imageStorage->deletePaths($paths);
    }
}
