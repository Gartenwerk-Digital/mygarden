<?php

namespace App\Http\Controllers\Plants;

use App\Actions\Plants\ApprovePlantSpecies;
use App\Actions\Plants\ApprovePlantVariety;
use App\Actions\Plants\RejectPlantSpecies;
use App\Actions\Plants\RejectPlantVariety;
use App\Enums\CatalogStatus;
use App\Enums\MoistureLevel;
use App\Enums\PlantCategory;
use App\Enums\PlantLifecycle;
use App\Enums\SoilType;
use App\Enums\SunExposure;
use App\Exceptions\CatalogActionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Plants\RejectPlantSpeciesRequest;
use App\Http\Requests\Plants\RejectPlantVarietyRequest;
use App\Models\PlantSpecies;
use App\Models\PlantVariety;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PlantModerationController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('moderate-catalog');

        $species = PlantSpecies::query()
            ->where('catalog_status', CatalogStatus::PendingReview->value)
            ->whereNull('archived_at')
            ->where('origin', 'user')
            ->with(['creator:id,name,username'])
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->paginate(20, ['*'], 'speciesPage')
            ->withQueryString();

        $varieties = PlantVariety::query()
            ->where('catalog_status', CatalogStatus::PendingReview->value)
            ->whereNull('archived_at')
            ->where('origin', 'user')
            ->whereHas('species', fn ($q) => $q
                ->where('catalog_status', CatalogStatus::Approved->value)
                ->whereNull('archived_at')
                ->whereNull('deleted_at')
            )
            ->with(['creator:id,name,username', 'species:id,common_name'])
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->paginate(20, ['*'], 'varietiesPage')
            ->withQueryString();

        return Inertia::render('moderation/plants/index', [
            'species' => $species,
            'varieties' => $varieties,
        ]);
    }

    public function showSpecies(PlantSpecies $plantSpecies): Response|RedirectResponse
    {
        Gate::authorize('moderate-catalog');

        if ($plantSpecies->catalog_status !== CatalogStatus::PendingReview || $plantSpecies->archived_at !== null) {
            Inertia::flash('toast', ['type' => 'warning', 'message' => __('Dieser Eintrag ist nicht mehr in Prüfung.')]);

            return to_route('moderation.plants.index');
        }

        $plantSpecies->load(['creator:id,name,username', 'reviewer:id,name,username', 'duplicateOf:id,common_name']);

        $approvedSpecies = PlantSpecies::query()
            ->where('catalog_status', CatalogStatus::Approved->value)
            ->whereNull('archived_at')
            ->whereNull('deleted_at')
            ->where('id', '!=', $plantSpecies->id)
            ->orderBy('common_name')
            ->get(['id', 'common_name']);

        return Inertia::render('moderation/plants/show', [
            'plantSpecies' => $plantSpecies,
            'approvedSpecies' => $approvedSpecies,
            'categories' => collect(PlantCategory::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()])->all(),
            'lifecycles' => collect(PlantLifecycle::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()])->all(),
            'sunExposures' => collect(SunExposure::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()])->all(),
            'soilTypes' => collect(SoilType::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()])->all(),
            'moistureLevels' => collect(MoistureLevel::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()])->all(),
        ]);
    }

    public function approveSpecies(Request $request, PlantSpecies $plantSpecies, ApprovePlantSpecies $action): RedirectResponse
    {
        Gate::authorize('approve', $plantSpecies);

        try {
            $action->handle($plantSpecies, $request->user());
        } catch (CatalogActionException $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return to_route('moderation.plants.index');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pflanzenart freigegeben.')]);

        return to_route('moderation.plants.index');
    }

    public function rejectSpecies(RejectPlantSpeciesRequest $request, PlantSpecies $plantSpecies, RejectPlantSpecies $action): RedirectResponse
    {
        try {
            $action->handle(
                $plantSpecies,
                $request->user(),
                $request->validated('moderation_note'),
                $request->validated('duplicate_of_id') !== null ? (int) $request->validated('duplicate_of_id') : null,
            );
        } catch (CatalogActionException $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return to_route('moderation.plants.index');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pflanzenart abgelehnt.')]);

        return to_route('moderation.plants.index');
    }

    public function showVariety(PlantSpecies $plantSpecies, PlantVariety $variety): Response|RedirectResponse
    {
        Gate::authorize('moderate-catalog');

        $parentActive = $plantSpecies->catalog_status === CatalogStatus::Approved
            && $plantSpecies->archived_at === null
            && $plantSpecies->deleted_at === null;

        if (
            $variety->catalog_status !== CatalogStatus::PendingReview
            || $variety->archived_at !== null
            || ! $parentActive
        ) {
            Inertia::flash('toast', ['type' => 'warning', 'message' => __('Diese Sorte ist nicht mehr in Prüfung.')]);

            return to_route('moderation.plants.index');
        }

        $variety->load(['creator:id,name,username', 'reviewer:id,name,username', 'duplicateOf:id,name']);

        $approvedVarieties = PlantVariety::query()
            ->where('plant_species_id', $plantSpecies->id)
            ->where('catalog_status', CatalogStatus::Approved->value)
            ->whereNull('archived_at')
            ->whereNull('deleted_at')
            ->where('id', '!=', $variety->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('moderation/plants/variety-show', [
            'plantSpecies' => $plantSpecies,
            'variety' => $variety,
            'approvedVarieties' => $approvedVarieties,
        ]);
    }

    public function approveVariety(Request $request, PlantSpecies $plantSpecies, PlantVariety $variety, ApprovePlantVariety $action): RedirectResponse
    {
        Gate::authorize('approve', $variety);

        try {
            $action->handle($variety, $request->user());
        } catch (CatalogActionException $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return to_route('moderation.plants.index');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sorte freigegeben.')]);

        return to_route('moderation.plants.index');
    }

    public function rejectVariety(RejectPlantVarietyRequest $request, PlantSpecies $plantSpecies, PlantVariety $variety, RejectPlantVariety $action): RedirectResponse
    {
        try {
            $action->handle(
                $variety,
                $request->user(),
                $request->validated('moderation_note'),
                $request->validated('duplicate_of_id') !== null ? (int) $request->validated('duplicate_of_id') : null,
            );
        } catch (CatalogActionException $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return to_route('moderation.plants.index');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sorte abgelehnt.')]);

        return to_route('moderation.plants.index');
    }
}
