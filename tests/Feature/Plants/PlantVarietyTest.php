<?php

use App\Enums\CatalogOrigin;
use App\Models\PlantSpecies;
use App\Models\PlantVariety;
use App\Models\User;

function validVarietyData(array $overrides = []): array
{
    return array_merge([
        'name' => 'Hidcote',
        'breeder' => null,
        'year_introduced' => null,
        'description' => null,
    ], $overrides);
}

// --- Authentifizierung ---

test('unauthenticated user cannot access variety create', function () {
    $species = PlantSpecies::factory()->system()->create();

    $this->get(route('plants.varieties.create', $species))->assertRedirect(route('login'));
});

test('unauthenticated user cannot store variety', function () {
    $species = PlantSpecies::factory()->system()->create();

    $this->post(route('plants.varieties.store', $species), validVarietyData())->assertRedirect(route('login'));
});

// --- Erstellungsregeln ---

test('variety for active system species is allowed', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->system()->create();

    $this->actingAs($user)
        ->post(route('plants.varieties.store', $species), validVarietyData())
        ->assertRedirect(route('plants.show', $species));

    expect(PlantVariety::count())->toBe(1);
});

test('variety for own active user species is allowed', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($user)->create();

    $this->actingAs($user)
        ->post(route('plants.varieties.store', $species), validVarietyData())
        ->assertRedirect();

    expect(PlantVariety::count())->toBe(1);
});

test('variety for foreign user species is forbidden', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($owner)->create();

    $this->actingAs($other)
        ->post(route('plants.varieties.store', $species), validVarietyData())
        ->assertForbidden();
});

test('variety for archived species is forbidden', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->system()->archived()->create();

    $this->actingAs($user)
        ->get(route('plants.varieties.create', $species))
        ->assertForbidden();
});

// --- Store ---

test('name is stored without surrounding quotation marks', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->system()->create();

    $this->actingAs($user)
        ->post(route('plants.varieties.store', $species), validVarietyData(['name' => "'Hidcote'"]));

    expect(PlantVariety::first()->name)->toBe('Hidcote');
});

test('duplicate name within same species is rejected', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->system()->create();
    PlantVariety::factory()->system()->create(['plant_species_id' => $species->id, 'name' => 'Hidcote']);

    $this->actingAs($user)
        ->post(route('plants.varieties.store', $species), validVarietyData(['name' => 'Hidcote']))
        ->assertSessionHasErrors('name');
});

test('same variety name is allowed for different species', function () {
    $user = User::factory()->create();
    $species1 = PlantSpecies::factory()->system()->create();
    $species2 = PlantSpecies::factory()->system()->create();
    PlantVariety::factory()->system()->create(['plant_species_id' => $species1->id, 'name' => 'Hidcote']);

    $this->actingAs($user)
        ->post(route('plants.varieties.store', $species2), validVarietyData(['name' => 'Hidcote']))
        ->assertRedirect();
});

test('year_introduced below 1500 is rejected', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->system()->create();

    $this->actingAs($user)
        ->post(route('plants.varieties.store', $species), validVarietyData(['year_introduced' => 1499]))
        ->assertSessionHasErrors('year_introduced');
});

test('year_introduced in future is rejected', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->system()->create();

    $this->actingAs($user)
        ->post(route('plants.varieties.store', $species), validVarietyData(['year_introduced' => (int) date('Y') + 1]))
        ->assertSessionHasErrors('year_introduced');
});

test('variety year_introduced in current year is valid', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->system()->create();

    $this->actingAs($user)
        ->post(route('plants.varieties.store', $species), validVarietyData(['year_introduced' => (int) date('Y')]))
        ->assertSessionDoesntHaveErrors();
});

test('origin is set to user on create', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->system()->create();

    $this->actingAs($user)
        ->post(route('plants.varieties.store', $species), validVarietyData());

    $variety = PlantVariety::first();
    expect($variety->origin)->toBe(CatalogOrigin::User);
    expect($variety->created_by)->toBe($user->id);
});

test('empty breeder and description are stored as null', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->system()->create();

    $this->actingAs($user)
        ->post(route('plants.varieties.store', $species), validVarietyData(['breeder' => '', 'description' => '']));

    $variety = PlantVariety::first();
    expect($variety->breeder)->toBeNull();
    expect($variety->description)->toBeNull();
});

// --- Update ---

test('creator can update own variety', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->system()->create();
    $variety = PlantVariety::factory()->forUser($user)->create(['plant_species_id' => $species->id, 'name' => 'Alt']);

    $this->actingAs($user)
        ->patch(route('plants.varieties.update', [$species, $variety]), validVarietyData(['name' => 'Neu']))
        ->assertRedirect();

    expect($variety->fresh()->name)->toBe('Neu');
});

test('other user cannot update variety', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $species = PlantSpecies::factory()->system()->create();
    $variety = PlantVariety::factory()->forUser($owner)->create(['plant_species_id' => $species->id]);

    $this->actingAs($other)
        ->patch(route('plants.varieties.update', [$species, $variety]), validVarietyData())
        ->assertForbidden();
});

test('system variety cannot be updated', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->system()->create();
    $variety = PlantVariety::factory()->system()->create(['plant_species_id' => $species->id]);

    $this->actingAs($user)
        ->patch(route('plants.varieties.update', [$species, $variety]), validVarietyData())
        ->assertForbidden();
});

// --- Archive / Unarchive ---

test('creator can archive own variety', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->system()->create();
    $variety = PlantVariety::factory()->forUser($user)->create(['plant_species_id' => $species->id]);

    $this->actingAs($user)
        ->patch(route('plants.varieties.archive', [$species, $variety]))
        ->assertRedirect();

    expect($variety->fresh()->archived_at)->not->toBeNull();
});

test('archive variety is idempotent', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->system()->create();
    $variety = PlantVariety::factory()->forUser($user)->archived()->create(['plant_species_id' => $species->id]);

    $this->actingAs($user)
        ->patch(route('plants.varieties.archive', [$species, $variety]));

    expect($variety->fresh()->archived_at)->not->toBeNull();
});

test('archived variety is not visible to other users', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $species = PlantSpecies::factory()->system()->create();
    PlantVariety::factory()->forUser($owner)->archived()->create(['plant_species_id' => $species->id, 'name' => 'Versteckt']);

    $this->actingAs($other)
        ->get(route('plants.show', $species))
        ->assertInertia(fn ($page) => $page
            ->where('archivedVarieties', [])
        );
});

// --- Delete ---

test('creator can delete own variety', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->system()->create();
    $variety = PlantVariety::factory()->forUser($user)->create(['plant_species_id' => $species->id]);

    $this->actingAs($user)
        ->delete(route('plants.varieties.destroy', [$species, $variety]))
        ->assertRedirect();

    expect(PlantVariety::find($variety->id))->toBeNull();
});

test('other user cannot delete variety', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $species = PlantSpecies::factory()->system()->create();
    $variety = PlantVariety::factory()->forUser($owner)->create(['plant_species_id' => $species->id]);

    $this->actingAs($other)
        ->delete(route('plants.varieties.destroy', [$species, $variety]))
        ->assertForbidden();
});
