<?php

namespace App\Actions\PlantingEntries;

use App\Models\PlantingEntry;
use App\Models\User;
use App\Support\ImageStorage;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class DeletePlantingEntry
{
    public function __construct(private readonly ImageStorage $imageStorage) {}

    public function handle(PlantingEntry $entry, User $user): void
    {
        if ($user->id !== $entry->planting->garden->user_id) {
            throw new AuthorizationException;
        }

        $paths = $entry->images()->pluck('path')->all();

        DB::transaction(function () use ($entry): void {
            $entry->images()->delete();
            $entry->delete();
        });

        $this->imageStorage->deletePaths($paths);
    }
}
