<?php

namespace App\Http\Controllers\GardenPlantings;

use App\Actions\PlantingEntries\CreatePlantingEntry;
use App\Actions\PlantingEntries\DeletePlantingEntry;
use App\Actions\PlantingEntries\UpdatePlantingEntry;
use App\Enums\EntryType;
use App\Http\Controllers\Controller;
use App\Http\Requests\PlantingEntries\StorePlantingEntryRequest;
use App\Http\Requests\PlantingEntries\UpdatePlantingEntryRequest;
use App\Models\Garden;
use App\Models\GardenPlanting;
use App\Models\PlantingEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PlantingEntryController extends Controller
{
    public function create(Garden $garden, GardenPlanting $planting): Response
    {
        Gate::authorize('create', [PlantingEntry::class, $planting]);

        return Inertia::render('planting-entries/create', [
            'garden' => $garden,
            'planting' => $planting->load(['species', 'variety']),
            'entryTypes' => $this->entryTypeOptions(),
        ]);
    }

    public function store(StorePlantingEntryRequest $request, Garden $garden, GardenPlanting $planting, CreatePlantingEntry $action): RedirectResponse
    {
        $action->handle(
            $planting,
            $request->user(),
            $request->validated(),
            $request->file('images') ?? []
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Eintrag angelegt.')]);

        return to_route('plantings.show', [$garden, $planting]);
    }

    public function edit(Garden $garden, GardenPlanting $planting, PlantingEntry $entry): Response
    {
        Gate::authorize('update', $entry);

        return Inertia::render('planting-entries/edit', [
            'garden' => $garden,
            'planting' => $planting->load(['species', 'variety']),
            'entry' => $entry->load('images')->toTimelineArray(),
            'entryTypes' => $this->entryTypeOptions(),
        ]);
    }

    public function update(UpdatePlantingEntryRequest $request, Garden $garden, GardenPlanting $planting, PlantingEntry $entry, UpdatePlantingEntry $action): RedirectResponse
    {
        $action->handle(
            $entry,
            $request->user(),
            $request->validated(),
            $request->file('images') ?? [],
            $request->input('remove_image_ids') ?? []
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Eintrag aktualisiert.')]);

        return to_route('plantings.show', [$garden, $planting]);
    }

    public function destroy(Garden $garden, GardenPlanting $planting, PlantingEntry $entry, DeletePlantingEntry $action): RedirectResponse
    {
        Gate::authorize('delete', $entry);

        $action->handle($entry, request()->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Eintrag gelöscht.')]);

        return to_route('plantings.show', [$garden, $planting]);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function entryTypeOptions(): array
    {
        return array_map(
            fn (EntryType $t) => ['value' => $t->value, 'label' => $t->label()],
            EntryType::cases()
        );
    }
}
