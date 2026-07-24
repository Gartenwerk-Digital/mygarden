<?php

namespace App\Http\Controllers\GardenPlantings;

use App\Http\Controllers\Controller;
use App\Models\Garden;
use App\Models\GardenPlanting;
use App\Models\PlantingEntry;
use App\Models\PlantingEntryImage;
use App\Support\ImageStorage;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlantingEntryImageController extends Controller
{
    public function __construct(private readonly ImageStorage $imageStorage) {}

    public function show(Garden $garden, GardenPlanting $planting, PlantingEntry $entry, PlantingEntryImage $image): StreamedResponse
    {
        Gate::authorize('view', $planting);

        if (! $this->imageStorage->exists($image->path)) {
            abort(404);
        }

        if (! array_key_exists($image->mime_type, ImageStorage::MIME_EXTENSION_MAP)) {
            abort(404);
        }

        return $this->imageStorage->privateResponse($image->path, $image->mime_type);
    }
}
