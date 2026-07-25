<?php

namespace App\Actions\Gardens;

use App\Models\Garden;
use App\Models\GardenImage;
use App\Models\User;
use App\Support\ImageStorage;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UploadGardenImages
{
    public const MAX_PER_REQUEST = 5;

    public const MAX_PER_GARDEN = 20;

    public const MAX_BYTES_PER_FILE = 3 * 1024 * 1024;

    public function __construct(private readonly ImageStorage $imageStorage) {}

    /**
     * @param  UploadedFile[]  $files
     * @return Collection<int, GardenImage>
     */
    public function handle(User $user, Garden $garden, array $files): Collection
    {
        Gate::forUser($user)->authorize('update', $garden);

        if ($garden->trashed()) {
            throw new AuthorizationException;
        }

        $this->assertFileCountBounds($files);
        $this->assertFilesValid($files);

        $uploadedPaths = [];

        try {
            $images = DB::transaction(function () use (&$uploadedPaths, $garden, $files): Collection {
                /** @var Garden $locked */
                $locked = Garden::query()->whereKey($garden->id)->lockForUpdate()->firstOrFail();

                $existing = $locked->images()->count();
                if ($existing + count($files) > self::MAX_PER_GARDEN) {
                    throw ValidationException::withMessages([
                        'images' => __('Ein Garten kann höchstens :max Bilder enthalten.', ['max' => self::MAX_PER_GARDEN]),
                    ]);
                }

                $maxSort = (int) (GardenImage::query()->where('garden_id', $locked->id)->max('sort_order') ?? -1);

                $created = collect();
                foreach ($files as $index => $file) {
                    $stored = $this->imageStorage->store($file, "gardens/{$locked->id}");
                    $uploadedPaths[] = $stored->path;

                    $created->push($locked->images()->create([
                        'path' => $stored->path,
                        'mime_type' => $stored->mimeType,
                        'size' => $stored->size,
                        'sort_order' => $maxSort + 1 + $index,
                        'is_cover' => null,
                    ]));
                }

                return $created;
            });
        } catch (\Throwable $e) {
            $this->imageStorage->deletePaths($uploadedPaths);
            throw $e;
        }

        return $images;
    }

    /**
     * @param  UploadedFile[]  $files
     */
    private function assertFileCountBounds(array $files): void
    {
        if (count($files) < 1) {
            throw ValidationException::withMessages([
                'images' => __('Mindestens ein Bild ist erforderlich.'),
            ]);
        }

        if (count($files) > self::MAX_PER_REQUEST) {
            throw ValidationException::withMessages([
                'images' => __('Pro Upload sind höchstens :max Bilder erlaubt.', ['max' => self::MAX_PER_REQUEST]),
            ]);
        }
    }

    /**
     * @param  UploadedFile[]  $files
     */
    private function assertFilesValid(array $files): void
    {
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                throw ValidationException::withMessages([
                    'images' => __('Ungültige Datei.'),
                ]);
            }

            if (! array_key_exists((string) $file->getMimeType(), ImageStorage::MIME_EXTENSION_MAP)) {
                throw ValidationException::withMessages([
                    'images' => __('Nicht erlaubter MIME-Typ.'),
                ]);
            }

            if ($file->getSize() > self::MAX_BYTES_PER_FILE) {
                throw ValidationException::withMessages([
                    'images' => __('Bild ist zu groß.'),
                ]);
            }
        }
    }
}
