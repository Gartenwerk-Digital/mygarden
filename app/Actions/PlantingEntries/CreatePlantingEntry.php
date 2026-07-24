<?php

namespace App\Actions\PlantingEntries;

use App\Models\GardenPlanting;
use App\Models\PlantingEntry;
use App\Models\User;
use App\Support\ImageStorage;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePlantingEntry
{
    public function __construct(private readonly ImageStorage $imageStorage) {}

    /**
     * @param  array<string, mixed>  $validated
     * @param  UploadedFile[]  $images
     */
    public function handle(GardenPlanting $planting, User $user, array $validated, array $images = []): PlantingEntry
    {
        if ($user->id !== $planting->garden->user_id) {
            throw new AuthorizationException;
        }

        $this->assertHasContent($validated, $images);

        $uploadedPaths = [];

        try {
            $entry = DB::transaction(function () use (&$uploadedPaths, $planting, $validated, $images): PlantingEntry {
                $entry = $planting->entries()->create([
                    'entry_type' => $validated['entry_type'],
                    'notes' => $validated['notes'] ?? null,
                    'occurred_at' => $validated['occurred_at'],
                ]);

                foreach ($images as $index => $file) {
                    $stored = $this->imageStorage->store($file, "planting-entries/{$entry->id}");
                    $uploadedPaths[] = $stored->path;
                    $entry->images()->create([
                        'path' => $stored->path,
                        'mime_type' => $stored->mimeType,
                        'size' => $stored->size,
                        'sort_order' => $index,
                    ]);
                }

                return $entry;
            });
        } catch (\Throwable $e) {
            $this->imageStorage->deletePaths($uploadedPaths);
            throw $e;
        }

        return $entry;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  UploadedFile[]  $images
     */
    private function assertHasContent(array $validated, array $images): void
    {
        $hasNotes = ! empty(trim((string) ($validated['notes'] ?? '')));
        $hasImages = count($images) > 0;

        if (! $hasNotes && ! $hasImages) {
            throw ValidationException::withMessages([
                'notes' => __('Ein Eintrag muss mindestens eine Notiz oder ein Bild enthalten.'),
            ]);
        }
    }
}
