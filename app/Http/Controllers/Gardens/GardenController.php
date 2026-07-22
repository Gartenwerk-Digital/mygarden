<?php

namespace App\Http\Controllers\Gardens;

use App\Actions\Gardens\ArchiveGarden;
use App\Actions\Gardens\CreateGarden;
use App\Actions\Gardens\UnarchiveGarden;
use App\Actions\Gardens\UpdateGarden;
use App\Data\Countries;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gardens\StoreGardenRequest;
use App\Http\Requests\Gardens\UpdateGardenRequest;
use App\Models\Garden;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class GardenController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $activeGardens = $user->gardens()
            ->whereNull('archived_at')
            ->orderByDesc('updated_at')
            ->get();

        $archivedGardens = $user->gardens()
            ->whereNotNull('archived_at')
            ->orderByDesc('updated_at')
            ->get();

        return Inertia::render('gardens/index', [
            'activeGardens' => $activeGardens,
            'archivedGardens' => $archivedGardens,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('gardens/create', [
            'countries' => Countries::LIST,
        ]);
    }

    public function store(StoreGardenRequest $request, CreateGarden $action): RedirectResponse
    {
        $action->handle($request->user(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Garten erstellt.')]);

        return to_route('gardens.index');
    }

    public function show(Request $request, Garden $garden): Response
    {
        Gate::authorize('view', $garden);

        $activeAreas = $garden->areas()
            ->with(['children' => fn ($q) => $q->whereNull('archived_at')->orderBy('name')])
            ->whereNull('parent_id')
            ->whereNull('archived_at')
            ->orderBy('name')
            ->get();

        $activeAreas->each(function ($area): void {
            $area->setRelation('archivedChildren', $area->children()->whereNotNull('archived_at')->orderBy('name')->get());
        });

        $archivedAreas = $garden->areas()
            ->whereNull('parent_id')
            ->whereNotNull('archived_at')
            ->orderBy('name')
            ->get();

        $plantingsPage = max(1, (int) $request->query('plantingsPage', 1));

        $plantings = $garden->plantings()
            ->with([
                'species:id,common_name,category,archived_at,deleted_at',
                'variety:id,name,archived_at,deleted_at',
                'area:id,name,archived_at,deleted_at',
            ])
            ->whereNull('deleted_at')
            ->orderByRaw('CASE status WHEN "planted" THEN 0 WHEN "planned" THEN 1 WHEN "removed" THEN 2 ELSE 3 END')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(24, ['*'], 'plantingsPage', $plantingsPage);

        return Inertia::render('gardens/show', [
            'garden' => $garden,
            'activeAreas' => $activeAreas,
            'archivedAreas' => $archivedAreas,
            'plantings' => $plantings,
        ]);
    }

    public function edit(Request $request, Garden $garden): Response
    {
        Gate::authorize('update', $garden);

        return Inertia::render('gardens/edit', [
            'garden' => $garden,
            'countries' => Countries::LIST,
        ]);
    }

    public function update(UpdateGardenRequest $request, Garden $garden, UpdateGarden $action): RedirectResponse
    {
        $action->handle($garden, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Garten aktualisiert.')]);

        return to_route('gardens.show', $garden);
    }

    public function archive(Request $request, Garden $garden, ArchiveGarden $action): RedirectResponse
    {
        Gate::authorize('archive', $garden);

        $action->handle($garden);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Garten archiviert.')]);

        return to_route('gardens.show', $garden);
    }

    public function unarchive(Request $request, Garden $garden, UnarchiveGarden $action): RedirectResponse
    {
        Gate::authorize('unarchive', $garden);

        $action->handle($garden);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Garten aktiviert.')]);

        return to_route('gardens.show', $garden);
    }

    public function destroy(Request $request, Garden $garden): RedirectResponse
    {
        Gate::authorize('delete', $garden);

        $garden->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Garten gelöscht.')]);

        return to_route('gardens.index');
    }
}
