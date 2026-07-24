<?php

namespace App\Http\Controllers\GardenPlantings;

use App\Actions\GardenPlantings\CreateGardenPlanting;
use App\Actions\GardenPlantings\DeleteGardenPlanting;
use App\Actions\GardenPlantings\UpdateGardenPlanting;
use App\Enums\PlantingStatus;
use App\Enums\PlantingUnit;
use App\Http\Controllers\Controller;
use App\Http\Requests\GardenPlantings\StoreGardenPlantingRequest;
use App\Http\Requests\GardenPlantings\UpdateGardenPlantingRequest;
use App\Models\Area;
use App\Models\Garden;
use App\Models\GardenPlanting;
use App\Models\PlantSpecies;
use App\Models\PlantVariety;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class GardenPlantingController extends Controller
{
    public function create(Request $request, Garden $garden): Response
    {
        Gate::authorize('create', [GardenPlanting::class, $garden]);

        $preselectedAreaId = null;

        if ($request->filled('area')) {
            $area = Area::whereNull('deleted_at')
                ->whereNull('archived_at')
                ->where('garden_id', $garden->id)
                ->find($request->integer('area'));

            $preselectedAreaId = $area?->id;
        }

        return Inertia::render('plantings/create', [
            'garden' => $garden,
            'areas' => $this->availableAreas($garden),
            'availableSpecies' => $this->availableSpecies($request),
            'statuses' => $this->statusOptions(),
            'units' => $this->unitOptions(),
            'preselectedAreaId' => $preselectedAreaId,
        ]);
    }

    public function store(StoreGardenPlantingRequest $request, Garden $garden, CreateGardenPlanting $action): RedirectResponse
    {
        $action->handle($garden, $request->user(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pflanzung angelegt.')]);

        return to_route('gardens.show', $garden);
    }

    public function show(Request $request, Garden $garden, GardenPlanting $planting): Response
    {
        Gate::authorize('view', $planting);

        $planting->load(['species', 'variety', 'area']);

        $entries = $planting->entries()
            ->with('images:id,planting_entry_id,mime_type,size,sort_order,created_at')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'entriesPage')
            ->withQueryString()
            ->through(fn ($entry) => [
                'id' => $entry->id,
                'garden_planting_id' => $entry->garden_planting_id,
                'entry_type' => $entry->entry_type->value,
                'notes' => $entry->notes,
                'occurred_at' => $entry->occurred_at->format('Y-m-d'),
                'created_at' => $entry->created_at,
                'images' => $entry->images->map(fn ($img) => [
                    'id' => $img->id,
                    'planting_entry_id' => $img->planting_entry_id,
                    'mime_type' => $img->mime_type,
                    'size' => $img->size,
                    'sort_order' => $img->sort_order,
                    'created_at' => $img->created_at,
                ])->values()->all(),
            ]);

        return Inertia::render('plantings/show', [
            'garden' => $garden,
            'planting' => $planting,
            'entries' => $entries,
        ]);
    }

    public function edit(Request $request, Garden $garden, GardenPlanting $planting): Response
    {
        Gate::authorize('update', $planting);

        return Inertia::render('plantings/edit', [
            'garden' => $garden,
            'planting' => $planting->load(['species', 'variety', 'area']),
            'areas' => $this->availableAreas($garden),
            'availableSpecies' => $this->availableSpecies($request),
            'statuses' => $this->statusOptions(),
            'units' => $this->unitOptions(),
        ]);
    }

    public function update(UpdateGardenPlantingRequest $request, Garden $garden, GardenPlanting $planting, UpdateGardenPlanting $action): RedirectResponse
    {
        $action->handle($planting, $garden, $request->user(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pflanzung aktualisiert.')]);

        return to_route('plantings.show', [$garden, $planting]);
    }

    public function destroy(Garden $garden, GardenPlanting $planting, DeleteGardenPlanting $action): RedirectResponse
    {
        Gate::authorize('delete', $planting);

        $action->handle($planting);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pflanzung gelöscht.')]);

        return to_route('gardens.show', $garden);
    }

    /**
     * @return Collection<int, array{id: int, name: string, full_label: string}>
     */
    private function availableAreas(Garden $garden): Collection
    {
        return $garden->areas()
            ->with('parent:id,name')
            ->whereNull('archived_at')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id'])
            ->map(fn (Area $area) => [
                'id' => $area->id,
                'name' => $area->name,
                'full_label' => $area->parent
                    ? $area->parent->name.' › '.$area->name
                    : $area->name,
            ]);
    }

    /**
     * @return array<int, array{id: int, common_name: string, botanical_name: string|null, alternate_name: string|null, origin: 'system'|'user', catalog_status: 'approved'|'pending_review'|'private'|'rejected', varieties: array<int, array{id: int, name: string, catalog_status: 'approved'|'pending_review'|'private'|'rejected'}>}>
     */
    private function availableSpecies(Request $request): array
    {
        return PlantSpecies::selectableForPlanting($request->user())
            ->with(['varieties' => fn ($q) => $q->selectableForPlanting($request->user())->select(['id', 'plant_species_id', 'name', 'catalog_status'])])
            ->orderBy('common_name')
            ->get(['id', 'common_name', 'botanical_name', 'alternate_name', 'origin', 'catalog_status'])
            ->map(fn (PlantSpecies $species) => [
                'id' => $species->id,
                'common_name' => $species->common_name,
                'botanical_name' => $species->botanical_name,
                'alternate_name' => $species->alternate_name,
                'origin' => $species->origin->value,
                'catalog_status' => $species->catalog_status->value,
                'varieties' => $species->varieties->map(fn (PlantVariety $v) => [
                    'id' => $v->id,
                    'name' => $v->name,
                    'catalog_status' => $v->catalog_status->value,
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return array_map(
            fn (PlantingStatus $s) => ['value' => $s->value, 'label' => $s->label()],
            PlantingStatus::cases()
        );
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function unitOptions(): array
    {
        return array_map(
            fn (PlantingUnit $u) => ['value' => $u->value, 'label' => $u->label()],
            PlantingUnit::cases()
        );
    }
}
