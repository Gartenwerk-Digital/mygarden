<?php

use App\Enums\AreaType;
use App\Enums\MoistureLevel;
use App\Enums\SoilType;
use App\Enums\SunExposure;
use App\Models\Area;
use App\Models\Garden;
use App\Models\User;

function validAreaData(array $overrides = []): array
{
    return array_merge([
        'name' => 'Mein Beet',
        'type' => AreaType::FlowerBed->value,
        'description' => null,
        'size_sqm' => null,
        'sun_exposure' => null,
        'soil_type' => null,
        'moisture_level' => null,
        'parent_id' => null,
    ], $overrides);
}

// --- Authentifizierung ---

test('unauthenticated user is redirected from create area', function () {
    $garden = Garden::factory()->create();

    $this->get(route('areas.create', $garden))->assertRedirect(route('login'));
});

test('unauthenticated user cannot store area', function () {
    $garden = Garden::factory()->create();

    $this->post(route('areas.store', $garden), validAreaData())->assertRedirect(route('login'));
});

test('unauthenticated user is redirected from area show', function () {
    $garden = Garden::factory()->create();
    $area = Area::factory()->for($garden)->create();

    $this->get(route('areas.show', [$garden, $area]))->assertRedirect(route('login'));
});

// --- scopeBindings / Routing ---

test('area belonging to correct garden resolves', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $area = Area::factory()->for($garden)->create();

    $this->actingAs($user)
        ->get(route('areas.show', [$garden, $area]))
        ->assertOk();
});

test('area belonging to different garden returns 404', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $otherGarden = Garden::factory()->create(['user_id' => $user->id]);
    $area = Area::factory()->for($otherGarden)->create();

    $this->actingAs($user)
        ->get(route('areas.show', [$garden, $area]))
        ->assertNotFound();
});

test('soft deleted area returns 404', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $area = Area::factory()->for($garden)->create();
    $area->delete();

    $this->actingAs($user)
        ->get(route('areas.show', [$garden, $area]))
        ->assertNotFound();
});

// --- Autorisierung ---

test('owner can view their own area', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $area = Area::factory()->for($garden)->create();

    $this->actingAs($user)
        ->get(route('areas.show', [$garden, $area]))
        ->assertOk();
});

test('stranger cannot view area', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $owner->id]);
    $area = Area::factory()->for($garden)->create();

    $this->actingAs($stranger)
        ->get(route('areas.show', [$garden, $area]))
        ->assertForbidden();
});

test('stranger cannot edit area', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $owner->id]);
    $area = Area::factory()->for($garden)->create();

    $this->actingAs($stranger)
        ->get(route('areas.edit', [$garden, $area]))
        ->assertForbidden();
});

test('stranger cannot archive area', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $owner->id]);
    $area = Area::factory()->for($garden)->create();

    $this->actingAs($stranger)
        ->patch(route('areas.archive', [$garden, $area]))
        ->assertForbidden();
});

test('stranger cannot delete area', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $owner->id]);
    $area = Area::factory()->for($garden)->create();

    $this->actingAs($stranger)
        ->delete(route('areas.destroy', [$garden, $area]))
        ->assertForbidden();
});

test('manipulated garden_id in request does not move area to other garden', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $otherGarden = Garden::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('areas.store', $garden), validAreaData(['garden_id' => $otherGarden->id]))
        ->assertSessionHasNoErrors();

    expect(Area::latest()->first()->garden_id)->toBe($garden->id);
});

test('manipulated parent_id from foreign garden is rejected', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $otherGarden = Garden::factory()->create(['user_id' => $user->id]);
    $foreignRoot = Area::factory()->for($otherGarden)->create();

    $this->actingAs($user)
        ->post(route('areas.store', $garden), validAreaData(['parent_id' => $foreignRoot->id]))
        ->assertSessionHasErrors('parent_id');
});

// --- ?parent Query-Parameter ---

test('valid root parent is pre-filled when using parent query param', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $root = Area::factory()->for($garden)->create();

    $this->actingAs($user)
        ->get(route('areas.create', $garden).'?parent='.$root->id)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('areas/create')
            ->where('parentId', $root->id)
        );
});

test('parent from other garden is not pre-filled', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $otherGarden = Garden::factory()->create(['user_id' => $user->id]);
    $foreignRoot = Area::factory()->for($otherGarden)->create();

    $this->actingAs($user)
        ->get(route('areas.create', $garden).'?parent='.$foreignRoot->id)
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('parentId', null));
});

test('child area is not pre-filled as parent', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $root = Area::factory()->for($garden)->create();
    $child = Area::factory()->withParent($root)->create();

    $this->actingAs($user)
        ->get(route('areas.create', $garden).'?parent='.$child->id)
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('parentId', null));
});

test('archived area is not pre-filled as parent', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $archived = Area::factory()->archived()->for($garden)->create();

    $this->actingAs($user)
        ->get(route('areas.create', $garden).'?parent='.$archived->id)
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('parentId', null));
});

test('soft deleted area is not pre-filled as parent', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $deleted = Area::factory()->for($garden)->create();
    $deleted->delete();

    $this->actingAs($user)
        ->get(route('areas.create', $garden).'?parent='.$deleted->id)
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('parentId', null));
});

// --- Erstellen / Speichern ---

test('area can be created', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('areas.store', $garden), validAreaData(['name' => 'Neues Beet']))
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($garden->areas()->where('name', 'Neues Beet')->exists())->toBeTrue();
});

test('name is required', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('areas.store', $garden), validAreaData(['name' => '']))
        ->assertSessionHasErrors('name');
});

test('name max 100 characters', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('areas.store', $garden), validAreaData(['name' => str_repeat('a', 101)]))
        ->assertSessionHasErrors('name');
});

test('type is required', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('areas.store', $garden), validAreaData(['type' => '']))
        ->assertSessionHasErrors('type');
});

test('invalid type is rejected', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('areas.store', $garden), validAreaData(['type' => 'invalid_type']))
        ->assertSessionHasErrors('type');
});

test('description is trimmed', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('areas.store', $garden), validAreaData(['description' => '  Mein Beet  ']))
        ->assertSessionHasNoErrors();

    expect($garden->areas()->first()->description)->toBe('Mein Beet');
});

test('empty description becomes null', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('areas.store', $garden), validAreaData(['description' => '']))
        ->assertSessionHasNoErrors();

    expect($garden->areas()->first()->description)->toBeNull();
});

test('size_sqm min 0.01 is valid', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('areas.store', $garden), validAreaData(['size_sqm' => 0.01]))
        ->assertSessionHasNoErrors();
});

test('size_sqm 0 is rejected', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('areas.store', $garden), validAreaData(['size_sqm' => 0]))
        ->assertSessionHasErrors('size_sqm');
});

// --- Hierarchie ---

test('root area can be created', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('areas.store', $garden), validAreaData())
        ->assertSessionHasNoErrors();

    $area = $garden->areas()->first();
    expect($area)->not->toBeNull();
    expect($area->parent_id)->toBeNull();
});

test('child area can be created under root area', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $root = Area::factory()->for($garden)->create();

    $this->actingAs($user)
        ->post(route('areas.store', $garden), validAreaData(['parent_id' => $root->id]))
        ->assertSessionHasNoErrors();

    expect($garden->areas()->where('parent_id', $root->id)->exists())->toBeTrue();
});

test('child area cannot have another child area as parent', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $root = Area::factory()->for($garden)->create();
    $child = Area::factory()->withParent($root)->create();

    $this->actingAs($user)
        ->post(route('areas.store', $garden), validAreaData(['parent_id' => $child->id]))
        ->assertSessionHasErrors('parent_id');
});

test('area cannot be its own parent', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $area = Area::factory()->for($garden)->create();

    $this->actingAs($user)
        ->patch(route('areas.update', [$garden, $area]), validAreaData(['parent_id' => $area->id]))
        ->assertSessionHasErrors('parent_id');
});

test('archived parent is rejected as parent', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $archived = Area::factory()->archived()->for($garden)->create();

    $this->actingAs($user)
        ->post(route('areas.store', $garden), validAreaData(['parent_id' => $archived->id]))
        ->assertSessionHasErrors('parent_id');
});

test('soft deleted parent is rejected as parent', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $deleted = Area::factory()->for($garden)->create();
    $deleted->delete();

    $this->actingAs($user)
        ->post(route('areas.store', $garden), validAreaData(['parent_id' => $deleted->id]))
        ->assertSessionHasErrors('parent_id');
});

test('area with children cannot become a child itself', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $parent = Area::factory()->for($garden)->create();
    Area::factory()->withParent($parent)->create();
    $otherRoot = Area::factory()->for($garden)->create();

    $this->actingAs($user)
        ->patch(route('areas.update', [$garden, $parent]), validAreaData(['parent_id' => $otherRoot->id]))
        ->assertSessionHasErrors('parent_id');
});

test('area cannot reference parent from foreign garden', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $otherGarden = Garden::factory()->create(['user_id' => $user->id]);
    $foreignRoot = Area::factory()->for($otherGarden)->create();

    $this->actingAs($user)
        ->post(route('areas.store', $garden), validAreaData(['parent_id' => $foreignRoot->id]))
        ->assertSessionHasErrors('parent_id');
});

// --- Anzeige ---

test('show page renders correctly', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $area = Area::factory()->for($garden)->create();

    $this->actingAs($user)
        ->get(route('areas.show', [$garden, $area]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('areas/show')
            ->where('area.id', $area->id)
            ->where('garden.id', $garden->id)
        );
});

test('edit page renders correctly', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $area = Area::factory()->for($garden)->create();

    $this->actingAs($user)
        ->get(route('areas.edit', [$garden, $area]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('areas/edit')
            ->where('area.id', $area->id)
        );
});

test('garden show page contains areas', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    Area::factory()->for($garden)->create(['name' => 'Testbereich']);

    $this->actingAs($user)
        ->get(route('gardens.show', $garden))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('gardens/show')
            ->has('activeAreas', 1)
            ->where('activeAreas.0.name', 'Testbereich')
        );
});

// --- Aktualisieren ---

test('area can be updated', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $area = Area::factory()->for($garden)->create();

    $this->actingAs($user)
        ->patch(route('areas.update', [$garden, $area]), validAreaData([
            'name' => 'Aktualisierter Bereich',
            'type' => AreaType::Lawn->value,
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('areas.show', [$garden, $area]));

    expect($area->fresh()->name)->toBe('Aktualisierter Bereich');
    expect($area->fresh()->type)->toBe(AreaType::Lawn);
});

test('hierarchy validation also applies on update', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $root = Area::factory()->for($garden)->create();
    $child = Area::factory()->withParent($root)->create();

    $this->actingAs($user)
        ->patch(route('areas.update', [$garden, $child]), validAreaData(['parent_id' => $child->id]))
        ->assertSessionHasErrors('parent_id');
});

// --- Archivieren und Löschen ---

test('area without children can be archived', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $area = Area::factory()->for($garden)->create();

    $this->actingAs($user)
        ->patch(route('areas.archive', [$garden, $area]))
        ->assertSessionHasNoErrors();

    expect($area->fresh()->archived_at)->not->toBeNull();
});

test('archiving twice does not change the timestamp', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $area = Area::factory()->archived()->for($garden)->create();

    $originalTimestamp = $area->archived_at;

    $this->actingAs($user)
        ->patch(route('areas.archive', [$garden, $area]));

    expect($area->fresh()->archived_at->toDateTimeString())
        ->toBe($originalTimestamp->toDateTimeString());
});

test('area with active child cannot be archived', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $parent = Area::factory()->for($garden)->create();
    Area::factory()->withParent($parent)->create();

    $this->actingAs($user)
        ->patch(route('areas.archive', [$garden, $parent]))
        ->assertSessionHasErrors('children');
});

test('area with archived child cannot be archived', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $parent = Area::factory()->for($garden)->create();
    Area::factory()->withParent($parent)->archived()->create();

    $this->actingAs($user)
        ->patch(route('areas.archive', [$garden, $parent]))
        ->assertSessionHasErrors('children');
});

test('archived area can be unarchived', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $area = Area::factory()->archived()->for($garden)->create();

    $this->actingAs($user)
        ->patch(route('areas.unarchive', [$garden, $area]))
        ->assertSessionHasNoErrors();

    expect($area->fresh()->archived_at)->toBeNull();
});

test('unarchiving twice does not error', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $area = Area::factory()->for($garden)->create();

    $this->actingAs($user)
        ->patch(route('areas.unarchive', [$garden, $area]))
        ->assertSessionHasNoErrors();

    expect($area->fresh()->archived_at)->toBeNull();
});

test('area without children can be soft deleted', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $area = Area::factory()->for($garden)->create();

    $this->actingAs($user)
        ->delete(route('areas.destroy', [$garden, $area]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('gardens.show', $garden));

    expect(Area::find($area->id))->toBeNull();
    expect(Area::withTrashed()->find($area->id))->not->toBeNull();
});

test('area with active child cannot be deleted', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $parent = Area::factory()->for($garden)->create();
    Area::factory()->withParent($parent)->create();

    $this->actingAs($user)
        ->delete(route('areas.destroy', [$garden, $parent]))
        ->assertSessionHasErrors('children');
});

test('area with archived child cannot be deleted', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $parent = Area::factory()->for($garden)->create();
    Area::factory()->withParent($parent)->archived()->create();

    $this->actingAs($user)
        ->delete(route('areas.destroy', [$garden, $parent]))
        ->assertSessionHasErrors('children');
});

test('child can be archived and unarchived independently', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $parent = Area::factory()->for($garden)->create();
    $child = Area::factory()->withParent($parent)->create();

    $this->actingAs($user)
        ->patch(route('areas.archive', [$garden, $child]))
        ->assertSessionHasNoErrors();

    expect($child->fresh()->archived_at)->not->toBeNull();

    $this->actingAs($user)
        ->patch(route('areas.unarchive', [$garden, $child]))
        ->assertSessionHasNoErrors();

    expect($child->fresh()->archived_at)->toBeNull();
});

// --- Daten und Enums ---

test('enums are correctly cast', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('areas.store', $garden), validAreaData([
            'type' => AreaType::VegetableBed->value,
            'sun_exposure' => SunExposure::FullSun->value,
            'soil_type' => SoilType::Loamy->value,
            'moisture_level' => MoistureLevel::Moist->value,
        ]))
        ->assertSessionHasNoErrors();

    $area = $garden->areas()->first();
    expect($area->type)->toBe(AreaType::VegetableBed);
    expect($area->sun_exposure)->toBe(SunExposure::FullSun);
    expect($area->soil_type)->toBe(SoilType::Loamy);
    expect($area->moisture_level)->toBe(MoistureLevel::Moist);
});

test('nullable environment fields remain null', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('areas.store', $garden), validAreaData())
        ->assertSessionHasNoErrors();

    $area = $garden->areas()->first();
    expect($area->sun_exposure)->toBeNull();
    expect($area->soil_type)->toBeNull();
    expect($area->moisture_level)->toBeNull();
});

test('size_sqm is serialized as decimal string', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('areas.store', $garden), validAreaData(['size_sqm' => 12.5]))
        ->assertSessionHasNoErrors();

    $area = $garden->areas()->first();
    expect($area->size_sqm)->toBe('12.50');
});

test('invalid enum values are rejected by server', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('areas.store', $garden), validAreaData([
            'sun_exposure' => 'midnight_sun',
            'soil_type' => 'concrete',
            'moisture_level' => 'flooded',
        ]))
        ->assertSessionHasErrors(['sun_exposure', 'soil_type', 'moisture_level']);
});
