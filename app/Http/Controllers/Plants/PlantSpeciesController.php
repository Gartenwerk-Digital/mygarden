<?php

namespace App\Http\Controllers\Plants;

use App\Actions\Plants\ArchivePlantSpecies;
use App\Actions\Plants\CreatePlantSpecies;
use App\Actions\Plants\DeletePlantSpecies;
use App\Actions\Plants\SubmitPlantSpeciesForReview;
use App\Actions\Plants\UnarchivePlantSpecies;
use App\Actions\Plants\UpdatePlantSpecies;
use App\Actions\Plants\WithdrawPlantSpeciesSubmission;
use App\Enums\MoistureLevel;
use App\Enums\PlantCategory;
use App\Enums\PlantLifecycle;
use App\Enums\SoilType;
use App\Enums\SunExposure;
use App\Exceptions\CatalogActionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Plants\StorePlantSpeciesRequest;
use App\Http\Requests\Plants\UpdatePlantSpeciesRequest;
use App\Models\PlantSpecies;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PlantSpeciesController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $search = trim((string) $request->get('search', ''));
        $category = $request->get('category');
        $scope = $request->get('scope', 'catalog');
        $catalogStatus = $request->get('catalog_status');

        $allowedScopes = ['catalog', 'mine', 'archived'];
        if (! in_array($scope, $allowedScopes, strict: true)) {
            $scope = 'catalog';
        }

        $query = PlantSpecies::query()
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search): void {
                $escaped = addcslashes($search, '%_\\');
                $q->where('common_name', 'like', "%{$escaped}%")
                    ->orWhere('botanical_name', 'like', "%{$escaped}%")
                    ->orWhere('alternate_name', 'like', "%{$escaped}%");
            }))
            ->when(
                $category !== null && $category !== '' && PlantCategory::tryFrom((string) $category) !== null,
                fn ($q) => $q->where('category', $category),
            );

        match ($scope) {
            'catalog' => $query->whereNull('archived_at')->catalogVisibleTo($user),
            'mine' => $query->whereNull('archived_at')->where('created_by', $user->id),
            'archived' => $query->whereNotNull('archived_at')->where('created_by', $user->id),
            default => $query->whereNull('archived_at')->catalogVisibleTo($user),
        };

        if ($scope === 'mine' && $catalogStatus !== null && $catalogStatus !== '') {
            $query->where('catalog_status', $catalogStatus);
        }

        $species = $query->orderBy('common_name')->paginate(24)->withQueryString();

        return Inertia::render('plants/index', [
            'species' => $species,
            'filters' => [
                'search' => $search !== '' ? $search : null,
                'category' => $category,
                'scope' => $scope,
                'catalog_status' => $catalogStatus,
            ],
            'categories' => collect(PlantCategory::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()])->all(),
            'lifecycles' => collect(PlantLifecycle::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()])->all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('plants/create', [
            'categories' => collect(PlantCategory::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()])->all(),
            'lifecycles' => collect(PlantLifecycle::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()])->all(),
            'sunExposures' => collect(SunExposure::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()])->all(),
            'soilTypes' => collect(SoilType::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()])->all(),
            'moistureLevels' => collect(MoistureLevel::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()])->all(),
        ]);
    }

    public function store(StorePlantSpeciesRequest $request, CreatePlantSpecies $action): RedirectResponse
    {
        try {
            $species = $action->handle($request->user(), $request->validated());
        } catch (UniqueConstraintViolationException) {
            return back()->withErrors(['botanical_name' => 'Dieser botanische Name ist bereits im Katalog erfasst oder wird derzeit geprüft.'])->withInput();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pflanzenart erstellt.')]);

        return to_route('plants.show', $species);
    }

    public function show(Request $request, PlantSpecies $plantSpecies): Response
    {
        Gate::authorize('view', $plantSpecies);

        $user = $request->user();

        $plantSpecies->load([
            'varieties' => fn ($q) => $q->whereNull('archived_at')->catalogVisibleTo($user)->orderBy('name'),
        ]);

        // Load duplicate_of only when accessible (not archived, not deleted)
        if ($plantSpecies->duplicate_of_id !== null) {
            $plantSpecies->load('duplicateOf:id,common_name');
        }

        $archivedVarieties = $plantSpecies->varieties()
            ->whereNotNull('archived_at')
            ->where('created_by', $user->id)
            ->orderBy('name')
            ->get();

        return Inertia::render('plants/show', [
            'plantSpecies' => $plantSpecies,
            'archivedVarieties' => $archivedVarieties,
            'categories' => collect(PlantCategory::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()])->all(),
            'lifecycles' => collect(PlantLifecycle::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()])->all(),
            'sunExposures' => collect(SunExposure::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()])->all(),
            'soilTypes' => collect(SoilType::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()])->all(),
            'moistureLevels' => collect(MoistureLevel::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()])->all(),
        ]);
    }

    public function edit(PlantSpecies $plantSpecies): Response
    {
        Gate::authorize('update', $plantSpecies);

        return Inertia::render('plants/edit', [
            'plantSpecies' => $plantSpecies,
            'categories' => collect(PlantCategory::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()])->all(),
            'lifecycles' => collect(PlantLifecycle::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()])->all(),
            'sunExposures' => collect(SunExposure::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()])->all(),
            'soilTypes' => collect(SoilType::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()])->all(),
            'moistureLevels' => collect(MoistureLevel::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()])->all(),
        ]);
    }

    public function update(UpdatePlantSpeciesRequest $request, PlantSpecies $plantSpecies, UpdatePlantSpecies $action): RedirectResponse
    {
        try {
            $action->handle($plantSpecies, $request->validated());
        } catch (UniqueConstraintViolationException) {
            return back()->withErrors(['botanical_name' => 'Dieser botanische Name ist bereits im Katalog erfasst oder wird derzeit geprüft.'])->withInput();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pflanzenart aktualisiert.')]);

        return to_route('plants.show', $plantSpecies);
    }

    public function archive(PlantSpecies $plantSpecies, ArchivePlantSpecies $action): RedirectResponse
    {
        Gate::authorize('archive', $plantSpecies);

        try {
            $action->handle($plantSpecies);
        } catch (CatalogActionException $e) {
            return back()->withErrors(['action' => $e->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pflanzenart archiviert.')]);

        return to_route('plants.show', $plantSpecies);
    }

    public function unarchive(PlantSpecies $plantSpecies, UnarchivePlantSpecies $action): RedirectResponse
    {
        Gate::authorize('unarchive', $plantSpecies);

        $action->handle($plantSpecies);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pflanzenart aktiviert.')]);

        return to_route('plants.show', $plantSpecies);
    }

    public function destroy(PlantSpecies $plantSpecies, DeletePlantSpecies $action): RedirectResponse
    {
        Gate::authorize('delete', $plantSpecies);

        try {
            $action->handle($plantSpecies);
        } catch (CatalogActionException $e) {
            return back()->withErrors(['action' => $e->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pflanzenart gelöscht.')]);

        return to_route('plants.index');
    }

    public function submit(PlantSpecies $plantSpecies, SubmitPlantSpeciesForReview $action): RedirectResponse
    {
        Gate::authorize('submitForReview', $plantSpecies);

        try {
            $action->handle($plantSpecies);
        } catch (CatalogActionException $e) {
            return back()->withErrors(['action' => $e->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Pflanzenart zur Prüfung eingereicht.')]);

        return to_route('plants.show', $plantSpecies);
    }

    public function withdraw(PlantSpecies $plantSpecies, WithdrawPlantSpeciesSubmission $action): RedirectResponse
    {
        Gate::authorize('withdrawSubmission', $plantSpecies);

        try {
            $action->handle($plantSpecies);
        } catch (CatalogActionException $e) {
            return back()->withErrors(['action' => $e->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Einreichung zurückgezogen.')]);

        return to_route('plants.show', $plantSpecies);
    }
}
