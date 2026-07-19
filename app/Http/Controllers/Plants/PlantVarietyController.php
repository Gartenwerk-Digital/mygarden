<?php

namespace App\Http\Controllers\Plants;

use App\Actions\Plants\ArchivePlantVariety;
use App\Actions\Plants\CreatePlantVariety;
use App\Actions\Plants\DeletePlantVariety;
use App\Actions\Plants\UnarchivePlantVariety;
use App\Actions\Plants\UpdatePlantVariety;
use App\Http\Controllers\Controller;
use App\Http\Requests\Plants\StorePlantVarietyRequest;
use App\Http\Requests\Plants\UpdatePlantVarietyRequest;
use App\Models\PlantSpecies;
use App\Models\PlantVariety;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PlantVarietyController extends Controller
{
    public function create(PlantSpecies $plantSpecies): Response
    {
        Gate::authorize('create', [PlantVariety::class, $plantSpecies]);

        return Inertia::render('plants/varieties/create', [
            'plantSpecies' => $plantSpecies,
        ]);
    }

    public function store(StorePlantVarietyRequest $request, PlantSpecies $plantSpecies, CreatePlantVariety $action): RedirectResponse
    {
        $action->handle($request->user(), $plantSpecies, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sorte erstellt.')]);

        return to_route('plants.show', $plantSpecies);
    }

    public function edit(PlantSpecies $plantSpecies, PlantVariety $variety): Response
    {
        Gate::authorize('update', $variety);

        return Inertia::render('plants/varieties/edit', [
            'plantSpecies' => $plantSpecies,
            'variety' => $variety,
        ]);
    }

    public function update(UpdatePlantVarietyRequest $request, PlantSpecies $plantSpecies, PlantVariety $variety, UpdatePlantVariety $action): RedirectResponse
    {
        $action->handle($variety, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sorte aktualisiert.')]);

        return to_route('plants.show', $plantSpecies);
    }

    public function archive(PlantSpecies $plantSpecies, PlantVariety $variety, ArchivePlantVariety $action): RedirectResponse
    {
        Gate::authorize('archive', $variety);

        $action->handle($variety);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sorte archiviert.')]);

        return to_route('plants.show', $plantSpecies);
    }

    public function unarchive(PlantSpecies $plantSpecies, PlantVariety $variety, UnarchivePlantVariety $action): RedirectResponse
    {
        Gate::authorize('unarchive', $variety);

        $action->handle($variety);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sorte aktiviert.')]);

        return to_route('plants.show', $plantSpecies);
    }

    public function destroy(PlantSpecies $plantSpecies, PlantVariety $variety, DeletePlantVariety $action): RedirectResponse
    {
        Gate::authorize('delete', $variety);

        $action->handle($variety);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sorte gelöscht.')]);

        return to_route('plants.show', $plantSpecies);
    }
}
