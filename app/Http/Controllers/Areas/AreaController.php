<?php

namespace App\Http\Controllers\Areas;

use App\Actions\Areas\ArchiveArea;
use App\Actions\Areas\CreateArea;
use App\Actions\Areas\DeleteArea;
use App\Actions\Areas\UnarchiveArea;
use App\Actions\Areas\UpdateArea;
use App\Http\Controllers\Controller;
use App\Http\Requests\Areas\StoreAreaRequest;
use App\Http\Requests\Areas\UpdateAreaRequest;
use App\Models\Area;
use App\Models\Garden;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AreaController extends Controller
{
    public function create(Request $request, Garden $garden): Response
    {
        Gate::authorize('create', [Area::class, $garden]);

        $rootAreas = $garden->areas()
            ->whereNull('parent_id')
            ->whereNull('archived_at')
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'archived_at']);

        $parentId = null;
        $parentParam = $request->query('parent');

        if ($parentParam !== null) {
            $candidate = $garden->areas()
                ->whereNull('parent_id')
                ->whereNull('archived_at')
                ->find((int) $parentParam);

            if ($candidate !== null) {
                $parentId = $candidate->id;
            }
        }

        return Inertia::render('areas/create', [
            'garden' => $garden,
            'rootAreas' => $rootAreas,
            'parentId' => $parentId,
        ]);
    }

    public function store(StoreAreaRequest $request, Garden $garden, CreateArea $action): RedirectResponse
    {
        $area = $action->handle($garden, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Bereich erstellt.')]);

        return to_route('areas.show', [$garden, $area]);
    }

    public function show(Request $request, Garden $garden, Area $area): Response
    {
        Gate::authorize('view', $area);

        $area->load([
            'parent',
            'children' => fn ($query) => $query->orderBy('name'),
        ]);

        $areaPlantingsPage = max(1, (int) $request->query('areaPlantingsPage', 1));

        $plantings = $area->plantings()
            ->with([
                'species:id,common_name,category,archived_at,deleted_at',
                'variety:id,name,archived_at,deleted_at',
            ])
            ->whereNull('deleted_at')
            ->orderByRaw('CASE status WHEN "planted" THEN 0 WHEN "planned" THEN 1 WHEN "removed" THEN 2 ELSE 3 END')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(24, ['*'], 'areaPlantingsPage', $areaPlantingsPage);

        return Inertia::render('areas/show', [
            'garden' => $garden,
            'area' => $area,
            'plantings' => $plantings,
        ]);
    }

    public function edit(Garden $garden, Area $area): Response
    {
        Gate::authorize('update', $area);

        $hasChildren = $area->children()->exists();

        $rootAreasQuery = $garden->areas()
            ->whereNull('parent_id')
            ->whereNull('archived_at')
            ->where('id', '!=', $area->id);

        if ($hasChildren) {
            $rootAreasQuery->whereRaw('0 = 1');
        }

        $rootAreas = $rootAreasQuery->orderBy('name')->get(['id', 'name', 'type', 'archived_at']);

        return Inertia::render('areas/edit', [
            'garden' => $garden,
            'area' => $area,
            'rootAreas' => $rootAreas,
            'hasChildren' => $hasChildren,
        ]);
    }

    public function update(UpdateAreaRequest $request, Garden $garden, Area $area, UpdateArea $action): RedirectResponse
    {
        $action->handle($area, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Bereich aktualisiert.')]);

        return to_route('areas.show', [$garden, $area]);
    }

    public function archive(Garden $garden, Area $area, ArchiveArea $action): RedirectResponse
    {
        Gate::authorize('archive', $area);

        $action->handle($area);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Bereich archiviert.')]);

        return to_route('areas.show', [$garden, $area]);
    }

    public function unarchive(Garden $garden, Area $area, UnarchiveArea $action): RedirectResponse
    {
        Gate::authorize('unarchive', $area);

        $action->handle($area);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Bereich aktiviert.')]);

        return to_route('areas.show', [$garden, $area]);
    }

    public function destroy(Garden $garden, Area $area, DeleteArea $action): RedirectResponse
    {
        Gate::authorize('delete', $area);

        $action->handle($area);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Bereich gelöscht.')]);

        return to_route('gardens.show', $garden);
    }
}
