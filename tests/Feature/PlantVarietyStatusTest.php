<?php

use App\Enums\CatalogStatus;
use App\Enums\UserRole;
use App\Models\PlantSpecies;
use App\Models\PlantVariety;
use App\Models\User;

// --- Variety-Erstellungsregeln nach Status ---

test('variety creation for own private species is allowed', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($user)->create();

    $this->actingAs($user)
        ->get(route('plants.varieties.create', $species))
        ->assertOk();
});

test('variety creation for own rejected species is allowed', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($user)->rejected()->create();

    $this->actingAs($user)
        ->get(route('plants.varieties.create', $species))
        ->assertOk();
});

test('variety creation for own pending_review species is forbidden', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($user)->pendingReview()->create();

    $this->actingAs($user)
        ->get(route('plants.varieties.create', $species))
        ->assertForbidden();
});

test('variety creation for own approved species is forbidden', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($user)->approved()->create();

    $this->actingAs($user)
        ->get(route('plants.varieties.create', $species))
        ->assertForbidden();
});

test('new variety gets private status', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->system()->create();

    $this->actingAs($user)
        ->post(route('plants.varieties.store', $species), ['name' => 'TestSorte']);

    $variety = PlantVariety::where('name', 'TestSorte')->first();
    expect($variety->catalog_status)->toBe(CatalogStatus::Private);
});

// --- Variety-Sichtbarkeit ---

test('private variety is only visible to its creator in show page', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $species = PlantSpecies::factory()->system()->create();
    PlantVariety::factory()->forUser($owner)->create([
        'plant_species_id' => $species->id,
        'name' => 'Private Sorte',
    ]);

    $this->actingAs($owner)
        ->get(route('plants.show', $species))
        ->assertInertia(fn ($page) => $page
            ->has('plantSpecies.varieties', 1)
        );

    $this->actingAs($other)
        ->get(route('plants.show', $species))
        ->assertInertia(fn ($page) => $page
            ->has('plantSpecies.varieties', 0)
        );
});

test('pending_review variety is visible to moderators', function () {
    $owner = User::factory()->create();
    $moderator = User::factory()->create(['role' => UserRole::Moderator->value]);
    $species = PlantSpecies::factory()->system()->create();
    PlantVariety::factory()->forUser($owner)->pendingReview()->create([
        'plant_species_id' => $species->id,
        'name' => 'In-Prüfung-Sorte',
    ]);

    $this->actingAs($moderator)
        ->get(route('plants.show', $species))
        ->assertInertia(fn ($page) => $page
            ->has('plantSpecies.varieties', 1)
        );
});

// --- Variety-Status-Übergänge ---

test('creator can submit variety for review when species is approved', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($user)->approved()->create();
    $variety = PlantVariety::factory()->forUser($user)->create([
        'plant_species_id' => $species->id,
    ]);

    $this->actingAs($user)
        ->patch(route('plants.varieties.submit', [$species, $variety]))
        ->assertRedirect(route('plants.show', $species));

    expect($variety->fresh()->catalog_status)->toBe(CatalogStatus::PendingReview);
});

test('cannot submit variety when species is not approved', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($user)->create();
    $variety = PlantVariety::factory()->forUser($user)->create([
        'plant_species_id' => $species->id,
    ]);

    $this->actingAs($user)
        ->patch(route('plants.varieties.submit', [$species, $variety]))
        ->assertForbidden();
});

test('creator can withdraw variety submission', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($user)->approved()->create();
    $variety = PlantVariety::factory()->forUser($user)->pendingReview()->create([
        'plant_species_id' => $species->id,
    ]);

    $this->actingAs($user)
        ->patch(route('plants.varieties.withdraw', [$species, $variety]))
        ->assertRedirect(route('plants.show', $species));

    expect($variety->fresh()->catalog_status)->toBe(CatalogStatus::Private);
});

// --- Bearbeitungssperren ---

test('creator cannot edit pending_review variety', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->system()->create();
    $variety = PlantVariety::factory()->forUser($user)->pendingReview()->create([
        'plant_species_id' => $species->id,
    ]);

    $this->actingAs($user)
        ->get(route('plants.varieties.edit', [$species, $variety]))
        ->assertForbidden();
});

test('creator cannot edit approved variety', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->system()->create();
    $variety = PlantVariety::factory()->forUser($user)->approved()->create([
        'plant_species_id' => $species->id,
    ]);

    $this->actingAs($user)
        ->get(route('plants.varieties.edit', [$species, $variety]))
        ->assertForbidden();
});

test('catalog_status cannot be set via mass assignment on variety create', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->system()->create();

    $this->actingAs($user)
        ->post(route('plants.varieties.store', $species), [
            'name' => 'TestSorte',
            'catalog_status' => 'approved',
        ]);

    $variety = PlantVariety::where('name', 'TestSorte')->first();
    expect($variety->catalog_status)->toBe(CatalogStatus::Private);
});
