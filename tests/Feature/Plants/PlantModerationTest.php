<?php

use App\Enums\CatalogStatus;
use App\Enums\UserRole;
use App\Models\PlantSpecies;
use App\Models\PlantVariety;
use App\Models\User;

// --- Zugriffskontrolle ---

test('guest cannot access moderation index', function () {
    $this->get(route('moderation.plants.index'))->assertRedirect(route('login'));
});

test('regular user gets 403 on moderation index', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('moderation.plants.index'))->assertForbidden();
});

test('moderator can access moderation index', function () {
    $moderator = User::factory()->create(['role' => UserRole::Moderator->value]);
    $this->actingAs($moderator)->get(route('moderation.plants.index'))->assertOk();
});

test('admin can access moderation index', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin->value]);
    $this->actingAs($admin)->get(route('moderation.plants.index'))->assertOk();
});

test('regular user gets 403 on approve species', function () {
    $owner = User::factory()->create();
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($owner)->pendingReview()->create();

    $this->actingAs($user)->patch(route('moderation.plants.approve', $species))->assertForbidden();
});

// --- Moderation Index – Inhalt ---

test('moderation index shows only pending_review species', function () {
    $owner = User::factory()->create();
    $moderator = User::factory()->create(['role' => UserRole::Moderator->value]);
    PlantSpecies::factory()->forUser($owner)->pendingReview()->create(['common_name' => 'In Prüfung']);
    PlantSpecies::factory()->forUser($owner)->create(['common_name' => 'Privat']);
    PlantSpecies::factory()->system()->create(['common_name' => 'System']);

    $this->actingAs($moderator)
        ->get(route('moderation.plants.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('species.data', 1)
            ->where('species.data.0.common_name', 'In Prüfung')
        );
});

test('moderation index shows pending variety only when parent species is approved and active', function () {
    $owner = User::factory()->create();
    $moderator = User::factory()->create(['role' => UserRole::Moderator->value]);

    $approvedSpecies = PlantSpecies::factory()->forUser($owner)->approved()->create();
    PlantVariety::factory()->forUser($owner)->pendingReview()->create([
        'plant_species_id' => $approvedSpecies->id,
        'name' => 'Sichtbare Sorte',
    ]);

    $privateSpecies = PlantSpecies::factory()->forUser($owner)->create();
    PlantVariety::factory()->forUser($owner)->pendingReview()->create([
        'plant_species_id' => $privateSpecies->id,
        'name' => 'Versteckte Sorte',
    ]);

    $this->actingAs($moderator)
        ->get(route('moderation.plants.index'))
        ->assertInertia(fn ($page) => $page
            ->has('varieties.data', 1)
            ->where('varieties.data.0.name', 'Sichtbare Sorte')
        );
});

test('moderation index has two independent page parameters', function () {
    $moderator = User::factory()->create(['role' => UserRole::Moderator->value]);

    $this->actingAs($moderator)
        ->get(route('moderation.plants.index', ['speciesPage' => 1, 'varietiesPage' => 1]))
        ->assertOk();
});

// --- Approve Species ---

test('moderator can approve pending_review species', function () {
    $owner = User::factory()->create();
    $moderator = User::factory()->create(['role' => UserRole::Moderator->value]);
    $species = PlantSpecies::factory()->forUser($owner)->pendingReview()->create([
        'submitted_at' => now()->subDay(),
    ]);

    $this->actingAs($moderator)
        ->patch(route('moderation.plants.approve', $species))
        ->assertRedirect(route('moderation.plants.index'));

    $species->refresh();
    expect($species->catalog_status)->toBe(CatalogStatus::Approved);
    expect($species->reviewed_by)->toBe($moderator->id);
    expect($species->reviewed_at)->not->toBeNull();
    expect($species->moderation_note)->toBeNull();
    expect($species->duplicate_of_id)->toBeNull();
    expect($species->submitted_at)->not->toBeNull(); // preserved after approve
});

test('approve clears old moderation note and duplicate_of_id', function () {
    $owner = User::factory()->create();
    $moderator = User::factory()->create(['role' => UserRole::Moderator->value]);
    $species = PlantSpecies::factory()->forUser($owner)->pendingReview()->create([
        'moderation_note' => 'Old note',
        'duplicate_of_id' => null,
    ]);

    $this->actingAs($moderator)->patch(route('moderation.plants.approve', $species));

    $species->refresh();
    expect($species->moderation_note)->toBeNull();
    expect($species->duplicate_of_id)->toBeNull();
});

test('cannot approve non-pending species – action returns flash error', function () {
    $owner = User::factory()->create();
    $moderator = User::factory()->create(['role' => UserRole::Moderator->value]);
    $species = PlantSpecies::factory()->forUser($owner)->approved()->create();

    $this->actingAs($moderator)
        ->patch(route('moderation.plants.approve', $species))
        ->assertRedirect(route('moderation.plants.index'));

    // Status unchanged
    expect($species->fresh()->catalog_status)->toBe(CatalogStatus::Approved);
});

// --- Reject Species ---

test('moderator can reject pending_review species with note', function () {
    $owner = User::factory()->create();
    $moderator = User::factory()->create(['role' => UserRole::Moderator->value]);
    $species = PlantSpecies::factory()->forUser($owner)->pendingReview()->create();

    $this->actingAs($moderator)
        ->patch(route('moderation.plants.reject', $species), [
            'moderation_note' => 'Bitte botanischen Namen ergänzen.',
        ])
        ->assertRedirect(route('moderation.plants.index'));

    $species->refresh();
    expect($species->catalog_status)->toBe(CatalogStatus::Rejected);
    expect($species->moderation_note)->toBe('Bitte botanischen Namen ergänzen.');
    expect($species->reviewed_by)->toBe($moderator->id);
    expect($species->reviewed_at)->not->toBeNull();
});

test('reject with duplicate_of_id only is allowed', function () {
    $owner = User::factory()->create();
    $moderator = User::factory()->create(['role' => UserRole::Moderator->value]);
    $species = PlantSpecies::factory()->forUser($owner)->pendingReview()->create();
    $duplicate = PlantSpecies::factory()->system()->create(); // system() sets approved + origin

    $this->actingAs($moderator)
        ->patch(route('moderation.plants.reject', $species), [
            'duplicate_of_id' => $duplicate->id,
        ])
        ->assertRedirect(route('moderation.plants.index'));

    expect($species->fresh()->catalog_status)->toBe(CatalogStatus::Rejected);
    expect($species->fresh()->duplicate_of_id)->toBe($duplicate->id);
});

test('reject without note and without duplicate fails validation', function () {
    $owner = User::factory()->create();
    $moderator = User::factory()->create(['role' => UserRole::Moderator->value]);
    $species = PlantSpecies::factory()->forUser($owner)->pendingReview()->create();

    $this->actingAs($moderator)
        ->patch(route('moderation.plants.reject', $species), [
            'moderation_note' => '',
            'duplicate_of_id' => '',
        ])
        ->assertSessionHasErrors(['moderation_note', 'duplicate_of_id']);
});

test('reject with only whitespace note and no duplicate fails validation', function () {
    $owner = User::factory()->create();
    $moderator = User::factory()->create(['role' => UserRole::Moderator->value]);
    $species = PlantSpecies::factory()->forUser($owner)->pendingReview()->create();

    $this->actingAs($moderator)
        ->patch(route('moderation.plants.reject', $species), [
            'moderation_note' => '   ',
        ])
        ->assertSessionHasErrors(['moderation_note']);
});

test('duplicate_of_id cannot be the same species', function () {
    $owner = User::factory()->create();
    $moderator = User::factory()->create(['role' => UserRole::Moderator->value]);
    $species = PlantSpecies::factory()->forUser($owner)->pendingReview()->create();

    $this->actingAs($moderator)
        ->patch(route('moderation.plants.reject', $species), [
            'duplicate_of_id' => $species->id,
        ])
        ->assertSessionHasErrors(['duplicate_of_id']);
});

test('duplicate_of_id must be approved and not archived', function () {
    $owner = User::factory()->create();
    $moderator = User::factory()->create(['role' => UserRole::Moderator->value]);
    $species = PlantSpecies::factory()->forUser($owner)->pendingReview()->create();

    $privateTarget = PlantSpecies::factory()->forUser($owner)->create(); // private status
    $this->actingAs($moderator)
        ->patch(route('moderation.plants.reject', $species), [
            'duplicate_of_id' => $privateTarget->id,
        ])
        ->assertSessionHasErrors(['duplicate_of_id']);
});

// --- Re-Submit nach Reject ---

test('re-submit after reject clears reviewed fields and updates submitted_at', function () {
    $user = User::factory()->create();
    $moderator = User::factory()->create(['role' => UserRole::Moderator->value]);
    $species = PlantSpecies::factory()->forUser($user)->rejected()->create([
        'reviewed_by' => $moderator->id,
        'reviewed_at' => now()->subDay(),
        'moderation_note' => 'Ältere Ablehnung',
        'submitted_at' => now()->subDays(5),
    ]);

    $this->actingAs($user)
        ->patch(route('plants.submit', $species))
        ->assertRedirect(route('plants.show', $species));

    $species->refresh();
    expect($species->catalog_status)->toBe(CatalogStatus::PendingReview);
    expect($species->reviewed_by)->toBeNull();
    expect($species->reviewed_at)->toBeNull();
    expect($species->moderation_note)->toBeNull();
    expect($species->submitted_at)->not->toBeNull();
    expect($species->submitted_at->isToday())->toBeTrue();
});

// --- Withdraw ---

test('withdraw clears submitted_at and moderation fields', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($user)->pendingReview()->create([
        'submitted_at' => now()->subDay(),
    ]);

    $this->actingAs($user)
        ->patch(route('plants.withdraw', $species))
        ->assertRedirect(route('plants.show', $species));

    $species->refresh();
    expect($species->catalog_status)->toBe(CatalogStatus::Private);
    expect($species->submitted_at)->toBeNull();
    expect($species->reviewed_by)->toBeNull();
});

// --- submitted_at Audit-Feld ---

test('submitted_at is set when submitting for review', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($user)->create();

    $this->actingAs($user)->patch(route('plants.submit', $species));

    expect($species->fresh()->submitted_at)->not->toBeNull();
    expect($species->fresh()->submitted_at->isToday())->toBeTrue();
});

// --- Sichtbarkeit nach Entscheidung ---

test('approved species appears in catalog for other users', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $moderator = User::factory()->create(['role' => UserRole::Moderator->value]);
    $species = PlantSpecies::factory()->forUser($owner)->pendingReview()->create(['common_name' => 'Zu Prüfen']);

    $this->actingAs($moderator)->patch(route('moderation.plants.approve', $species));

    $this->actingAs($other)
        ->get(route('plants.show', $species))
        ->assertOk();
});

test('rejected species is only visible to creator', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $moderator = User::factory()->create(['role' => UserRole::Moderator->value]);
    $species = PlantSpecies::factory()->forUser($owner)->pendingReview()->create();

    $this->actingAs($moderator)->patch(route('moderation.plants.reject', $species), [
        'moderation_note' => 'Test',
    ]);

    $this->actingAs($other)->get(route('plants.show', $species))->assertForbidden();
    $this->actingAs($owner)->get(route('plants.show', $species))->assertOk();
});

// --- Moderationsdetail für nicht-pending Einträge ---

test('moderation show redirects when species is not pending_review', function () {
    $moderator = User::factory()->create(['role' => UserRole::Moderator->value]);
    $owner = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($owner)->approved()->create();

    $this->actingAs($moderator)
        ->get(route('moderation.plants.show', $species))
        ->assertRedirect(route('moderation.plants.index'));
});

// --- scopeCatalogVisibleTo – Moderator sieht pending nicht im normalen Katalog ---

test('moderator does not see foreign pending_review species in catalog', function () {
    $owner = User::factory()->create();
    $moderator = User::factory()->create(['role' => UserRole::Moderator->value]);
    PlantSpecies::factory()->forUser($owner)->pendingReview()->create(['common_name' => 'Pending']);
    PlantSpecies::factory()->system()->create(['common_name' => 'System']);

    $this->actingAs($moderator)
        ->get(route('plants.index'))
        ->assertInertia(fn ($page) => $page
            ->has('species.data', 1)
            ->where('species.data.0.common_name', 'System')
        );
});

// --- Approve Variety ---

test('moderator can approve pending_review variety', function () {
    $owner = User::factory()->create();
    $moderator = User::factory()->create(['role' => UserRole::Moderator->value]);
    $species = PlantSpecies::factory()->forUser($owner)->approved()->create();
    $variety = PlantVariety::factory()->forUser($owner)->pendingReview()->create([
        'plant_species_id' => $species->id,
    ]);

    $this->actingAs($moderator)
        ->patch(route('moderation.plants.varieties.approve', [$species, $variety]))
        ->assertRedirect(route('moderation.plants.index'));

    expect($variety->fresh()->catalog_status)->toBe(CatalogStatus::Approved);
    expect($variety->fresh()->reviewed_by)->toBe($moderator->id);
});

test('approve variety fails when parent species is not approved', function () {
    $owner = User::factory()->create();
    $moderator = User::factory()->create(['role' => UserRole::Moderator->value]);
    $species = PlantSpecies::factory()->forUser($owner)->create(); // private
    $variety = PlantVariety::factory()->forUser($owner)->pendingReview()->create([
        'plant_species_id' => $species->id,
    ]);

    $this->actingAs($moderator)
        ->patch(route('moderation.plants.varieties.approve', [$species, $variety]))
        ->assertRedirect(route('moderation.plants.index'));

    // Status unchanged
    expect($variety->fresh()->catalog_status)->toBe(CatalogStatus::PendingReview);
});

// --- Reject Variety ---

test('moderator can reject pending_review variety', function () {
    $owner = User::factory()->create();
    $moderator = User::factory()->create(['role' => UserRole::Moderator->value]);
    $species = PlantSpecies::factory()->forUser($owner)->approved()->create();
    $variety = PlantVariety::factory()->forUser($owner)->pendingReview()->create([
        'plant_species_id' => $species->id,
    ]);

    $this->actingAs($moderator)
        ->patch(route('moderation.plants.varieties.reject', [$species, $variety]), [
            'moderation_note' => 'Diese Sorte existiert bereits.',
        ])
        ->assertRedirect(route('moderation.plants.index'));

    expect($variety->fresh()->catalog_status)->toBe(CatalogStatus::Rejected);
    expect($variety->fresh()->moderation_note)->toBe('Diese Sorte existiert bereits.');
});

test('variety duplicate_of_id must belong to same species', function () {
    $owner = User::factory()->create();
    $moderator = User::factory()->create(['role' => UserRole::Moderator->value]);
    $species = PlantSpecies::factory()->forUser($owner)->approved()->create();
    $variety = PlantVariety::factory()->forUser($owner)->pendingReview()->create([
        'plant_species_id' => $species->id,
    ]);
    $otherSpecies = PlantSpecies::factory()->system()->create();
    $otherVariety = PlantVariety::factory()->forUser($owner)->approved()->create([
        'plant_species_id' => $otherSpecies->id,
    ]);

    $this->actingAs($moderator)
        ->patch(route('moderation.plants.varieties.reject', [$species, $variety]), [
            'duplicate_of_id' => $otherVariety->id,
        ])
        ->assertSessionHasErrors(['duplicate_of_id']);
});

// --- Mass Assignment Schutz ---

test('catalog_status cannot be changed via mass assignment through normal routes', function () {
    $user = User::factory()->create();
    $species = PlantSpecies::factory()->forUser($user)->create();

    $this->actingAs($user)->patch(route('plants.update', $species), [
        'common_name' => 'Updated Name',
        'category' => 'herb',
        'catalog_status' => 'approved', // attempt mass assignment
        'reviewed_by' => 999,
    ]);

    $species->refresh();
    expect($species->catalog_status)->toBe(CatalogStatus::Private);
    expect($species->reviewed_by)->toBeNull();
});

// --- Creator null-safe ---

test('moderation index renders without error when creator was deleted', function () {
    $owner = User::factory()->create();
    $moderator = User::factory()->create(['role' => UserRole::Moderator->value]);

    $species = PlantSpecies::factory()->forUser($owner)->pendingReview()->create();
    $owner->delete();

    $this->actingAs($moderator)
        ->get(route('moderation.plants.index'))
        ->assertOk();
});

// --- Race Condition Schutz ---

test('second approve on already approved species redirects with error', function () {
    $owner = User::factory()->create();
    $moderator = User::factory()->create(['role' => UserRole::Moderator->value]);
    $species = PlantSpecies::factory()->forUser($owner)->pendingReview()->create();

    $this->actingAs($moderator)->patch(route('moderation.plants.approve', $species));

    // Second approve – already approved
    $this->actingAs($moderator)
        ->patch(route('moderation.plants.approve', $species))
        ->assertRedirect(route('moderation.plants.index'));

    // Status not changed back
    expect($species->fresh()->catalog_status)->toBe(CatalogStatus::Approved);
});
