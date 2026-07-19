<?php

use App\Enums\CatalogOrigin;
use App\Enums\MoistureLevel;
use App\Enums\PlantCategory;
use App\Enums\SoilType;
use App\Enums\SunExposure;
use App\Models\PlantSpecies;
use App\Models\PlantVariety;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

function validSpeciesData(array $overrides = []): array
{
    return array_merge([
        'common_name' => 'Lavendel',
        'botanical_name' => null,
        'alternate_name' => null,
        'category' => PlantCategory::Flower->value,
        'description' => null,
        'lifecycle' => null,
        'hardiness_note' => null,
        'sun_exposures' => null,
        'soil_types' => null,
        'moisture_levels' => null,
        'height_min_cm' => null,
        'height_max_cm' => null,
    ], $overrides);
}

// --- Authentifizierung ---

test('unauthenticated user is redirected from plants index', function () {
    $this->get(route('plants.index'))->assertRedirect(route('login'));
});

test('unauthenticated user is redirected from plants create', function () {
    $this->get(route('plants.create'))->assertRedirect(route('login'));
});

test('unauthenticated user cannot store plant species', function () {
    $this->post(route('plants.store'), validSpeciesData())->assertRedirect(route('login'));
});

// --- Index ---

test('index shows active system and user species', function () {
    $user = User::factory()->create();
    $system = PlantSpecies::factory()->system()->create(['common_name' => 'Systemrose']);
    $userSpecies = PlantSpecies::factory()->forUser($user)->create(['common_name' => 'Nutzerlavendel']);

    $this->actingAs($user)
        ->get(route('plants.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('plants/index')
            ->has('species.data', 2)
        );
});

test('archived species are not shown in active index', function () {
    $user = User::factory()->create();
    PlantSpecies::factory()->forUser($user)->create();
    PlantSpecies::factory()->forUser($user)->archived()->create();

    $this->actingAs($user)
        ->get(route('plants.index'))
        ->assertInertia(fn ($page) => $page->has('species.data', 1));
});

test('status=archived shows only own archived species', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    PlantSpecies::factory()->forUser($user)->archived()->create(['common_name' => 'Eigene archiviert']);
    PlantSpecies::factory()->forUser($other)->archived()->create(['common_name' => 'Fremde archiviert']);

    $this->actingAs($user)
        ->get(route('plants.index', ['status' => 'archived']))
        ->assertInertia(fn ($page) => $page
            ->has('species.data', 1)
            ->where('species.data.0.common_name', 'Eigene archiviert')
        );
});

test('search filters by common_name', function () {
    $user = User::factory()->create();
    PlantSpecies::factory()->forUser($user)->create(['common_name' => 'Lavendel']);
    PlantSpecies::factory()->forUser($user)->create(['common_name' => 'Rose']);

    $this->actingAs($user)
        ->get(route('plants.index', ['search' => 'laven']))
        ->assertInertia(fn ($page) => $page->has('species.data', 1));
});

test('search filters by botanical_name', function () {
    $user = User::factory()->create();
    PlantSpecies::factory()->forUser($user)->create([
        'common_name' => 'Lavendel',
        'botanical_name' => 'Lavandula angustifolia',
    ]);
    PlantSpecies::factory()->forUser($user)->create(['common_name' => 'Rose']);

    $this->actingAs($user)
        ->get(route('plants.index', ['search' => 'lavandula']))
        ->assertInertia(fn ($page) => $page->has('species.data', 1));
});

test('search filters by alternate_name', function () {
    $user = User::factory()->create();
    PlantSpecies::factory()->forUser($user)->create([
        'common_name' => 'Echter Lavendel',
        'alternate_name' => 'Schmalblättriger Lavendel',
    ]);
    PlantSpecies::factory()->forUser($user)->create(['common_name' => 'Rose']);

    $this->actingAs($user)
        ->get(route('plants.index', ['search' => 'schmalblättriger']))
        ->assertInertia(fn ($page) => $page->has('species.data', 1));
});

test('category filter works', function () {
    $user = User::factory()->create();
    PlantSpecies::factory()->forUser($user)->create(['category' => PlantCategory::Flower->value]);
    PlantSpecies::factory()->forUser($user)->create(['category' => PlantCategory::Vegetable->value]);

    $this->actingAs($user)
        ->get(route('plants.index', ['category' => 'flower']))
        ->assertInertia(fn ($page) => $page->has('species.data', 1));
});

test('invalid category is silently ignored', function () {
    $user = User::factory()->create();
    PlantSpecies::factory()->forUser($user)->create();

    $this->actingAs($user)
        ->get(route('plants.index', ['category' => 'invalid_xyz']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('species.data', 1));
});

// --- Show ---

test('authenticated user can view active species', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->system()->create();

    $this->actingAs($user)
        ->get(route('plants.show', $species))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('plants/show'));
});

test('archived species is only visible to creator', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($owner)->archived()->create();

    $this->actingAs($owner)
        ->get(route('plants.show', $species))
        ->assertOk();

    $this->actingAs($other)
        ->get(route('plants.show', $species))
        ->assertForbidden();
});

// --- Store ---

test('common_name is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('plants.store'), validSpeciesData(['common_name' => '']))
        ->assertSessionHasErrors('common_name');
});

test('botanical_name is optional – multiple species without it allowed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('plants.store'), validSpeciesData(['common_name' => 'Pflanze A', 'botanical_name' => null]))
        ->assertRedirect();

    $this->actingAs($user)
        ->post(route('plants.store'), validSpeciesData(['common_name' => 'Pflanze B', 'botanical_name' => null]))
        ->assertRedirect();

    expect(PlantSpecies::count())->toBe(2);
});

test('duplicate botanical_name is rejected', function () {
    $user = User::factory()->create();
    PlantSpecies::factory()->system()->create(['botanical_name' => 'Rosa canina']);

    $this->actingAs($user)
        ->post(route('plants.store'), validSpeciesData(['botanical_name' => 'Rosa canina']))
        ->assertSessionHasErrors('botanical_name');
});

test('botanical_name is trimmed on store', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('plants.store'), validSpeciesData(['botanical_name' => '  Rosa canina  ']))
        ->assertSessionDoesntHaveErrors();

    expect(PlantSpecies::first()->botanical_name)->toBe('Rosa canina');
});

test('origin is set to user on store', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('plants.store'), validSpeciesData());

    $species = PlantSpecies::first();
    expect($species->origin)->toBe(CatalogOrigin::User);
    expect($species->created_by)->toBe($user->id);
});

test('origin cannot be manipulated via form data', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('plants.store'), array_merge(validSpeciesData(), ['origin' => 'system', 'created_by' => null]));

    $species = PlantSpecies::first();
    expect($species->origin)->toBe(CatalogOrigin::User);
    expect($species->created_by)->toBe($user->id);
});

test('json arrays for standort data are stored', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('plants.store'), validSpeciesData([
            'sun_exposures' => [SunExposure::FullSun->value, SunExposure::PartialShade->value],
            'soil_types' => [SoilType::Loamy->value],
            'moisture_levels' => [MoistureLevel::Normal->value, MoistureLevel::Moist->value],
        ]))
        ->assertSessionDoesntHaveErrors();

    $species = PlantSpecies::first();
    expect($species->sun_exposures)->toBe(['full_sun', 'partial_shade']);
    expect($species->soil_types)->toBe(['loamy']);
    expect($species->moisture_levels)->toBe(['normal', 'moist']);
});

test('duplicate values in json arrays are deduplicated and sorted by enum order', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('plants.store'), validSpeciesData([
            'sun_exposures' => ['partial_shade', 'full_sun', 'partial_shade'],
        ]))
        ->assertSessionDoesntHaveErrors();

    $species = PlantSpecies::first();
    expect($species->sun_exposures)->toBe(['full_sun', 'partial_shade']);
});

test('empty array is stored as null', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('plants.store'), validSpeciesData(['sun_exposures' => []]))
        ->assertSessionDoesntHaveErrors();

    expect(PlantSpecies::first()->sun_exposures)->toBeNull();
});

test('invalid enum value in json array is rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('plants.store'), validSpeciesData(['sun_exposures' => ['unknown_value']]))
        ->assertSessionHasErrors('sun_exposures.0');
});

test('height_min must be less than or equal to height_max', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('plants.store'), validSpeciesData(['height_min_cm' => 100, 'height_max_cm' => 50]))
        ->assertSessionHasErrors('height_max_cm');
});

test('height_max cannot exceed 60000', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('plants.store'), validSpeciesData(['height_max_cm' => 60001]))
        ->assertSessionHasErrors('height_max_cm');
});

// --- Update ---

test('creator can update own user species', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($user)->create(['common_name' => 'Alt']);

    $this->actingAs($user)
        ->patch(route('plants.update', $species), validSpeciesData(['common_name' => 'Neu']))
        ->assertRedirect(route('plants.show', $species));

    expect($species->fresh()->common_name)->toBe('Neu');
});

test('other user cannot update species', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($owner)->create();

    $this->actingAs($other)
        ->patch(route('plants.update', $species), validSpeciesData())
        ->assertForbidden();
});

test('system species cannot be updated', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->system()->create();

    $this->actingAs($user)
        ->patch(route('plants.update', $species), validSpeciesData())
        ->assertForbidden();
});

// --- Archive / Unarchive ---

test('creator can archive own species without varieties', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($user)->create();

    $this->actingAs($user)
        ->patch(route('plants.archive', $species))
        ->assertRedirect();

    expect($species->fresh()->archived_at)->not->toBeNull();
});

test('archive is idempotent', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($user)->archived()->create();

    $this->actingAs($user)
        ->patch(route('plants.archive', $species));

    expect($species->fresh()->archived_at)->not->toBeNull();
});

test('species with active variety cannot be archived', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($user)->create();
    PlantVariety::factory()->forUser($user)->create(['plant_species_id' => $species->id]);

    $this->actingAs($user)
        ->patch(route('plants.archive', $species))
        ->assertForbidden();
});

test('species with archived variety cannot be archived', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($user)->create();
    PlantVariety::factory()->forUser($user)->archived()->create(['plant_species_id' => $species->id]);

    $this->actingAs($user)
        ->patch(route('plants.archive', $species))
        ->assertForbidden();
});

test('creator can unarchive own species', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($user)->archived()->create();

    $this->actingAs($user)
        ->patch(route('plants.unarchive', $species))
        ->assertRedirect();

    expect($species->fresh()->archived_at)->toBeNull();
});

// --- Delete ---

test('creator can soft delete species without varieties', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($user)->create();

    $this->actingAs($user)
        ->delete(route('plants.destroy', $species))
        ->assertRedirect(route('plants.index'));

    expect(PlantSpecies::find($species->id))->toBeNull();
});

test('species with active variety cannot be soft deleted', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($user)->create();
    PlantVariety::factory()->forUser($user)->create(['plant_species_id' => $species->id]);

    $this->actingAs($user)
        ->delete(route('plants.destroy', $species))
        ->assertForbidden();
});

test('species with archived variety cannot be soft deleted', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($user)->create();
    PlantVariety::factory()->forUser($user)->archived()->create(['plant_species_id' => $species->id]);

    $this->actingAs($user)
        ->delete(route('plants.destroy', $species))
        ->assertForbidden();
});

test('species can be soft deleted after all varieties are soft deleted', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($user)->create();
    $variety = PlantVariety::factory()->forUser($user)->create(['plant_species_id' => $species->id]);

    $variety->delete();

    $this->actingAs($user)
        ->delete(route('plants.destroy', $species))
        ->assertRedirect(route('plants.index'));

    expect(PlantSpecies::find($species->id))->toBeNull();
});

test('force delete of species with soft-deleted variety is blocked by mysql', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($user)->create();
    PlantVariety::factory()->forUser($user)->create(['plant_species_id' => $species->id])->delete();

    expect(fn () => $species->forceDelete())->toThrow(QueryException::class);
});

// --- Herkunft nach Account-Löschung ---

test('created_by becomes null after user account deletion, origin remains user', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($user)->create();

    $user->delete();

    $species->refresh();
    expect($species->created_by)->toBeNull();
    expect($species->origin)->toBe(CatalogOrigin::User);
});

test('orphaned species with origin=user and created_by=null is not editable', function () {
    $other = User::factory()->create();
    $species = PlantSpecies::factory()->forUser(User::factory()->create())->create();
    $species->update(['created_by' => null]);

    $this->actingAs($other)
        ->patch(route('plants.update', $species), validSpeciesData())
        ->assertForbidden();
});

// --- Origin NOT NULL constraint ---

test('direct insert without origin fails due to NOT NULL constraint', function () {
    expect(fn () => DB::table('plant_species')->insert([
        'common_name' => 'Test',
        'category' => 'flower',
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});
