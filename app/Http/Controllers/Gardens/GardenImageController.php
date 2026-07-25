<?php

namespace App\Http\Controllers\Gardens;

/*
 * Authorization policy: GardenImage-Aktionen delegieren an GardenPolicy
 * (view / update). scopeBindings() erzwingt die Zugehörigkeit von {image}
 * zu {garden}. Actions prüfen die Zugehörigkeit zusätzlich defensiv.
 * Es gibt bewusst keine eigene GardenImagePolicy.
 */

use App\Actions\Gardens\DeleteGardenImage;
use App\Actions\Gardens\SetGardenCoverImage;
use App\Actions\Gardens\UnsetGardenCoverImage;
use App\Actions\Gardens\UploadGardenImages;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gardens\StoreGardenImagesRequest;
use App\Models\Garden;
use App\Models\GardenImage;
use App\Support\ImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GardenImageController extends Controller
{
    public function __construct(private readonly ImageStorage $imageStorage) {}

    public function store(StoreGardenImagesRequest $request, Garden $garden, UploadGardenImages $action): RedirectResponse
    {
        $action->handle($request->user(), $garden, $request->file('images', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Bilder hochgeladen.')]);

        return back();
    }

    public function show(Garden $garden, GardenImage $image): StreamedResponse
    {
        Gate::authorize('view', $garden);

        if (! $this->imageStorage->exists($image->path)) {
            abort(404);
        }

        if (! array_key_exists($image->mime_type, ImageStorage::MIME_EXTENSION_MAP)) {
            abort(404);
        }

        return $this->imageStorage->privateResponse($image->path, $image->mime_type);
    }

    public function destroy(Garden $garden, GardenImage $image, DeleteGardenImage $action): RedirectResponse
    {
        $action->handle(request()->user(), $garden, $image);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Bild gelöscht.')]);

        return back();
    }

    public function setCover(Garden $garden, GardenImage $image, SetGardenCoverImage $action): RedirectResponse
    {
        $action->handle(request()->user(), $garden, $image);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Titelbild gesetzt.')]);

        return back();
    }

    public function unsetCover(Garden $garden, UnsetGardenCoverImage $action): RedirectResponse
    {
        $action->handle(request()->user(), $garden);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Titelbild entfernt.')]);

        return back();
    }
}
