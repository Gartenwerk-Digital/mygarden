<?php

use App\Enums\CatalogStatus;
use App\Enums\UserRole;
use App\Models\PlantSpecies;
use App\Models\User;

// --- Sichtbarkeit nach Status ---

test('private species is only visible to its creator', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($owner)->create();

    $this->actingAs($owner)
        ->get(route('plants.show', $species))
        ->assertOk();

    $this->actingAs($other)
        ->get(route('plants.show', $species))
        ->assertForbidden();
});

test('private species does not appear in catalog index for other users', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    PlantSpecies::factory()->forUser($owner)->create(['common_name' => 'Privat-Pflanze']);

    $this->actingAs($other)
        ->get(route('plants.index'))
        ->assertInertia(fn ($page) => $page->has('species.data', 0));
});

test('private species appears in mine scope for creator', function () {
    $user = User::factory()->create();
    PlantSpecies::factory()->forUser($user)->create(['common_name' => 'Meine Pflanze']);

    $this->actingAs($user)
        ->get(route('plants.index', ['scope' => 'mine']))
        ->assertInertia(fn ($page) => $page->has('species.data', 1));
});

test('approved user species is visible to all verified users', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($owner)->approved()->create();

    $this->actingAs($other)
        ->get(route('plants.show', $species))
        ->assertOk();
});

test('pending_review species is not visible to regular users', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($owner)->pendingReview()->create();

    $this->actingAs($other)
        ->get(route('plants.show', $species))
        ->assertForbidden();
});

test('pending_review species is visible to moderators', function () {
    $owner = User::factory()->create();
    $moderator = User::factory()->create(['role' => UserRole::Moderator->value]);
    $species = PlantSpecies::factory()->forUser($owner)->pendingReview()->create();

    $this->actingAs($moderator)
        ->get(route('plants.show', $species))
        ->assertOk();
});

test('rejected species is only visible to creator', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($owner)->rejected()->create();

    $this->actingAs($other)
        ->get(route('plants.show', $species))
        ->assertForbidden();
});

test('archived species is not visible to moderators', function () {
    $owner = User::factory()->create();
    $moderator = User::factory()->create(['role' => UserRole::Moderator->value]);
    $species = PlantSpecies::factory()->forUser($owner)->archived()->create();

    $this->actingAs($moderator)
        ->get(route('plants.show', $species))
        ->assertForbidden();
});

test('scope=review returns only pending_review entries for moderator', function () {
    $owner = User::factory()->create();
    $moderator = User::factory()->create(['role' => UserRole::Moderator->value]);
    PlantSpecies::factory()->forUser($owner)->pendingReview()->create(['common_name' => 'In Prüfung']);
    PlantSpecies::factory()->forUser($owner)->create(['common_name' => 'Privat']);
    PlantSpecies::factory()->system()->create(['common_name' => 'System']);

    $this->actingAs($moderator)
        ->get(route('plants.index', ['scope' => 'review']))
        ->assertInertia(fn ($page) => $page
            ->has('species.data', 1)
            ->where('species.data.0.common_name', 'In Prüfung')
        );
});

test('scope=review returns 403 for regular users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('plants.index', ['scope' => 'review']))
        ->assertForbidden();
});

// --- Status-Übergänge ---

test('creator can submit private species for review', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($user)->create();

    $this->actingAs($user)
        ->patch(route('plants.submit', $species))
        ->assertRedirect(route('plants.show', $species));

    expect($species->fresh()->catalog_status)->toBe(CatalogStatus::PendingReview);
});

test('creator can submit rejected species for review', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($user)->rejected()->create();

    $this->actingAs($user)
        ->patch(route('plants.submit', $species))
        ->assertRedirect(route('plants.show', $species));

    expect($species->fresh()->catalog_status)->toBe(CatalogStatus::PendingReview);
});

test('cannot submit pending_review species again', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($user)->pendingReview()->create();

    $this->actingAs($user)
        ->patch(route('plants.submit', $species))
        ->assertForbidden();
});

test('cannot submit approved species', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($user)->approved()->create();

    $this->actingAs($user)
        ->patch(route('plants.submit', $species))
        ->assertForbidden();
});

test('creator can withdraw pending_review submission', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($user)->pendingReview()->create();

    $this->actingAs($user)
        ->patch(route('plants.withdraw', $species))
        ->assertRedirect(route('plants.show', $species));

    expect($species->fresh()->catalog_status)->toBe(CatalogStatus::Private);
});

test('cannot withdraw private species', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($user)->create();

    $this->actingAs($user)
        ->patch(route('plants.withdraw', $species))
        ->assertForbidden();
});

test('other user cannot submit species', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($owner)->create();

    $this->actingAs($other)
        ->patch(route('plants.submit', $species))
        ->assertForbidden();
});

// --- Bearbeitungssperren nach Status ---

test('creator can edit private species', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($user)->create();

    $this->actingAs($user)
        ->get(route('plants.edit', $species))
        ->assertOk();
});

test('creator cannot edit pending_review species', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($user)->pendingReview()->create();

    $this->actingAs($user)
        ->get(route('plants.edit', $species))
        ->assertForbidden();
});

test('creator cannot edit approved species', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($user)->approved()->create();

    $this->actingAs($user)
        ->get(route('plants.edit', $species))
        ->assertForbidden();
});

test('creator can edit rejected species', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($user)->rejected()->create();

    $this->actingAs($user)
        ->get(route('plants.edit', $species))
        ->assertOk();
});

// --- Manipulationsschutz ---

test('catalog_status cannot be set via mass assignment on create', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('plants.store'), [
            'common_name' => 'Test',
            'category' => 'flower',
            'catalog_status' => 'approved',
        ])
        ->assertRedirect();

    $species = PlantSpecies::where('common_name', 'Test')->first();
    expect($species->catalog_status)->toBe(CatalogStatus::Private);
});

// --- Botanisches Duplikat-Informationsleck ---

test('botanical duplicate error does not reveal hidden private species name', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    PlantSpecies::factory()->forUser($owner)->create(['botanical_name' => 'Quercus robur']);

    $response = $this->actingAs($other)
        ->post(route('plants.store'), [
            'common_name' => 'Eiche',
            'category' => 'tree',
            'botanical_name' => 'Quercus robur',
        ]);

    $response->assertSessionHasErrors('botanical_name');
    $errorMessage = session('errors')->get('botanical_name')[0];
    // Neutral error message - does not reveal the hidden entry's details
    expect($errorMessage)->toContain('erfasst oder wird derzeit geprüft');
});
