<?php

namespace App\Actions\PlantingEntries;

use App\Models\GardenPlanting;
use App\Models\PlantingEntry;
use App\Models\PlantingEntryImage;
use App\Models\User;
use App\Support\ImageStorage;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdatePlantingEntry
{
    public function __construct(private readonly ImageStorage $imageStorage) {}

    /**
     * @param  array<string, mixed>  $validated
     * @param  UploadedFile[]  $newImages
     * @param  int[]  $removeImageIds
     */
    public function handle(
        PlantingEntry $entry,
        User $user,
        array $validated,
        array $newImages = [],
        array $removeImageIds = [],
    ): PlantingEntry {
        /** @var GardenPlanting $planting */
        $planting = $entry->planting;

        if ($user->id !== $planting->garden->user_id) {
            throw new AuthorizationException;
        }

        $removeImageIds = array_values(array_unique($removeImageIds));
        $this->validateRemoveIds($entry, $removeImageIds);
        $this->validateImageLimit($entry, $removeImageIds, $newImages);
        $this->assertHasContentAfterUpdate($validated, $entry, $removeImageIds, $newImages);

        $pathsToDelete = PlantingEntryImage::whereIn('id', $removeImageIds)
            ->where('planting_entry_id', $entry->id)
            ->pluck('path')
            ->all();

        $uploadedPaths = [];

        try {
            DB::transaction(function () use (&$uploadedPaths, $entry, $validated, $removeImageIds, $newImages): void {
                $entry->update([
                    'entry_type' => $validated['entry_type'],
                    'notes' => $validated['notes'] ?? null,
                    'occurred_at' => $validated['occurred_at'],
                ]);

                if (! empty($removeImageIds)) {
                    $entry->images()->whereIn('id', $removeImageIds)->delete();
                }

                $nextSortOrder = $entry->images()->max('sort_order') ?? -1;

                foreach ($newImages as $file) {
                    $stored = $this->imageStorage->store($file, "planting-entries/{$entry->id}");
                    $uploadedPaths[] = $stored->path;
                    $nextSortOrder++;
                    $entry->images()->create([
                        'path' => $stored->path,
                        'mime_type' => $stored->mimeType,
                        'size' => $stored->size,
                        'sort_order' => $nextSortOrder,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            $this->imageStorage->deletePaths($uploadedPaths);
            throw $e;
        }

        $this->imageStorage->deletePaths($pathsToDelete);

        return $entry->fresh();
    }

    /**
     * @param  int[]  $removeImageIds
     */
    private function validateRemoveIds(PlantingEntry $entry, array $removeImageIds): void
    {
        if (empty($removeImageIds)) {
            return;
        }

        $validCount = PlantingEntryImage::whereIn('id', $removeImageIds)
            ->where('planting_entry_id', $entry->id)
            ->count();

        if ($validCount !== count($removeImageIds)) {
            throw ValidationException::withMessages([
                'remove_image_ids' => __('Einige Bilder gehören nicht zu diesem Eintrag.'),
            ]);
        }
    }

    /**
     * @param  int[]  $removeImageIds
     * @param  UploadedFile[]  $newImages
     */
    private function validateImageLimit(PlantingEntry $entry, array $removeImageIds, array $newImages): void
    {
        $existing = $entry->images()->count();
        $remaining = $existing - count($removeImageIds) + count($newImages);

        if ($remaining > 10) {
            throw ValidationException::withMessages([
                'images' => __('Ein Eintrag kann maximal 10 Bilder enthalten.'),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  int[]  $removeImageIds
     * @param  UploadedFile[]  $newImages
     */
    private function assertHasContentAfterUpdate(
        array $validated,
        PlantingEntry $entry,
        array $removeImageIds,
        array $newImages,
    ): void {
        $hasNotes = ! empty(trim((string) ($validated['notes'] ?? '')));

        $remainingImages = $entry->images()->count() - count($removeImageIds) + count($newImages);
        $hasImages = $remainingImages > 0;

        if (! $hasNotes && ! $hasImages) {
            throw ValidationException::withMessages([
                'notes' => __('Ein Eintrag muss mindestens eine Notiz oder ein Bild enthalten.'),
            ]);
        }
    }
}
