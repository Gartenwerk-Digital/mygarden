<?php

use App\Actions\Areas\DeleteArea;
use App\Actions\GardenPlantings\CreateGardenPlanting;
use App\Actions\GardenPlantings\UpdateGardenPlanting;
use App\Actions\Plants\DeletePlantSpecies;
use App\Actions\Plants\DeletePlantVariety;
use App\Enums\PlantingStatus;
use App\Enums\PlantingUnit;
use App\Exceptions\CatalogActionException;
use App\Models\Area;
use App\Models\Garden;
use App\Models\GardenPlanting;
use App\Models\PlantSpecies;
use App\Models\PlantVariety;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

function validPlantingData(array $overrides = []): array
{
    return array_merge([
        'plant_species_id' => null,
        'plant_variety_id' => null,
        'area_id' => null,
        'status' => PlantingStatus::Planned->value,
        'quantity' => null,
        'quantity_unit' => null,
        'nickname' => null,
        'notes' => null,
        'planted_at' => null,
        'acquired_at' => null,
    ], $overrides);
}

// --- Authentifizierung ---

test('unauthenticated user is redirected from create planting', function () {
    $garden = Garden::factory()->create();

    $this->get(route('plantings.create', $garden))->assertRedirect(route('login'));
});

test('unauthenticated user cannot store planting', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->system()->create();

    $this->post(route('plantings.store', $garden), validPlantingData(['plant_species_id' => $species->id]))
        ->assertRedirect(route('login'));
});

test('unauthenticated user is redirected from show planting', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->system()->create();
    $planting = GardenPlanting::factory()->for($garden)->for($species, 'species')->create();

    $this->get(route('plantings.show', [$garden, $planting]))->assertRedirect(route('login'));
});

// --- Autorisierung ---

test('owner can view their own planting', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->system()->create();
    $planting = GardenPlanting::factory()->for($garden)->for($species, 'species')->create();

    $this->actingAs($user)
        ->get(route('plantings.show', [$garden, $planting]))
        ->assertOk();
});

test('stranger cannot view planting', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $owner->id]);
    $species = PlantSpecies::factory()->system()->create();
    $planting = GardenPlanting::factory()->for($garden)->for($species, 'species')->create();

    $this->actingAs($stranger)
        ->get(route('plantings.show', [$garden, $planting]))
        ->assertForbidden();
});

test('stranger cannot create planting', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $owner->id]);
    $species = PlantSpecies::factory()->system()->create();

    $this->actingAs($stranger)
        ->post(route('plantings.store', $garden), validPlantingData(['plant_species_id' => $species->id]))
        ->assertForbidden();
});

test('stranger cannot update planting', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $owner->id]);
    $species = PlantSpecies::factory()->system()->create();
    $planting = GardenPlanting::factory()->for($garden)->for($species, 'species')->create();

    $this->actingAs($stranger)
        ->patch(route('plantings.update', [$garden, $planting]), validPlantingData(['plant_species_id' => $species->id]))
        ->assertForbidden();
});

test('stranger cannot delete planting', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $owner->id]);
    $species = PlantSpecies::factory()->system()->create();
    $planting = GardenPlanting::factory()->for($garden)->for($species, 'species')->create();

    $this->actingAs($stranger)
        ->delete(route('plantings.destroy', [$garden, $planting]))
        ->assertForbidden();
});

// --- scopeBindings ---

test('planting belonging to correct garden resolves', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->system()->create();
    $planting = GardenPlanting::factory()->for($garden)->for($species, 'species')->create();

    $this->actingAs($user)
        ->get(route('plantings.show', [$garden, $planting]))
        ->assertOk();
});

test('planting belonging to different garden returns 404', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $other = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->system()->create();
    $planting = GardenPlanting::factory()->for($other)->for($species, 'species')->create();

    $this->actingAs($user)
        ->get(route('plantings.show', [$garden, $planting]))
        ->assertNotFound();
});

test('soft deleted planting returns 404', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->system()->create();
    $planting = GardenPlanting::factory()->for($garden)->for($species, 'species')->create();
    $planting->delete();

    $this->actingAs($user)
        ->get(route('plantings.show', [$garden, $planting]))
        ->assertNotFound();
});

// --- CRUD ---

test('owner can create a planting', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->system()->create();

    $this->actingAs($user)
        ->post(route('plantings.store', $garden), validPlantingData(['plant_species_id' => $species->id]))
        ->assertRedirect(route('gardens.show', $garden));

    expect(GardenPlanting::where('garden_id', $garden->id)->count())->toBe(1);
});

test('owner can update a planting', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->system()->create();
    $planting = GardenPlanting::factory()->for($garden)->for($species, 'species')->create();

    $this->actingAs($user)
        ->patch(route('plantings.update', [$garden, $planting]), validPlantingData([
            'plant_species_id' => $species->id,
            'status' => PlantingStatus::Planted->value,
            'nickname' => 'Mein Baum',
        ]))
        ->assertRedirect(route('plantings.show', [$garden, $planting]));

    expect($planting->fresh()->nickname)->toBe('Mein Baum');
    expect($planting->fresh()->status)->toBe(PlantingStatus::Planted);
});

test('owner can delete a planting', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->system()->create();
    $planting = GardenPlanting::factory()->for($garden)->for($species, 'species')->create();

    $this->actingAs($user)
        ->delete(route('plantings.destroy', [$garden, $planting]))
        ->assertRedirect(route('gardens.show', $garden));

    expect(GardenPlanting::find($planting->id))->toBeNull();
    expect(GardenPlanting::withTrashed()->find($planting->id))->not->toBeNull();
});

// --- Status-Übergänge und removed_at ---

test('status change to removed sets removed_at', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->system()->create();
    $planting = GardenPlanting::factory()->for($garden)->for($species, 'species')->planted()->create();

    $this->actingAs($user)
        ->patch(route('plantings.update', [$garden, $planting]), validPlantingData([
            'plant_species_id' => $species->id,
            'status' => PlantingStatus::Removed->value,
        ]))
        ->assertRedirect();

    expect($planting->fresh()->removed_at)->not->toBeNull();
});

test('repeated removed update does not overwrite removed_at', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->system()->create();
    $originalTime = now()->subDays(5);
    $planting = GardenPlanting::factory()->for($garden)->for($species, 'species')->create([
        'status' => PlantingStatus::Removed->value,
        'removed_at' => $originalTime,
    ]);

    $this->actingAs($user)
        ->patch(route('plantings.update', [$garden, $planting]), validPlantingData([
            'plant_species_id' => $species->id,
            'status' => PlantingStatus::Removed->value,
        ]))
        ->assertRedirect();

    expect($planting->fresh()->removed_at->timestamp)->toBe($originalTime->timestamp);
});

test('reactivating removed planting clears removed_at', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->system()->create();
    $planting = GardenPlanting::factory()->for($garden)->for($species, 'species')->removed()->create();

    $this->actingAs($user)
        ->patch(route('plantings.update', [$garden, $planting]), validPlantingData([
            'plant_species_id' => $species->id,
            'status' => PlantingStatus::Planted->value,
        ]))
        ->assertRedirect();

    expect($planting->fresh()->removed_at)->toBeNull();
    expect($planting->fresh()->status)->toBe(PlantingStatus::Planted);
});

// --- Katalogregeln (Store) ---

test('rejected species cannot be used for new planting', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->forUser($user)->rejected()->create();

    $this->actingAs($user)
        ->post(route('plantings.store', $garden), validPlantingData(['plant_species_id' => $species->id]))
        ->assertSessionHasErrors('plant_species_id');
});

test('archived species cannot be used for new planting', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->system()->archived()->create();

    $this->actingAs($user)
        ->post(route('plantings.store', $garden), validPlantingData(['plant_species_id' => $species->id]))
        ->assertSessionHasErrors('plant_species_id');
});

test('foreign private species cannot be used for new planting', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $owner->id]);
    $species = PlantSpecies::factory()->forUser($other)->create();

    $this->actingAs($owner)
        ->post(route('plantings.store', $garden), validPlantingData(['plant_species_id' => $species->id]))
        ->assertSessionHasErrors('plant_species_id');
});

test('own pending_review species is allowed for new planting', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->forUser($user)->pendingReview()->create();

    $this->actingAs($user)
        ->post(route('plantings.store', $garden), validPlantingData(['plant_species_id' => $species->id]))
        ->assertRedirect(route('gardens.show', $garden));
});

test('approved system species is allowed for new planting', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->system()->create();

    $this->actingAs($user)
        ->post(route('plantings.store', $garden), validPlantingData(['plant_species_id' => $species->id]))
        ->assertRedirect(route('gardens.show', $garden));
});

// --- Katalogregeln (Update) ---

test('updating note with archived species does not fail', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->system()->create();
    $planting = GardenPlanting::factory()->for($garden)->for($species, 'species')->create();

    $species->update(['archived_at' => now()]);

    $this->actingAs($user)
        ->patch(route('plantings.update', [$garden, $planting]), [
            'plant_species_id' => $species->id,
            'status' => PlantingStatus::Planned->value,
            'notes' => 'Neue Notiz',
        ])
        ->assertRedirect();

    expect($planting->fresh()->notes)->toBe('Neue Notiz');
});

test('changing species to archived species is blocked', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $speciesA = PlantSpecies::factory()->system()->create();
    $speciesB = PlantSpecies::factory()->system()->archived()->create();
    $planting = GardenPlanting::factory()->for($garden)->for($speciesA, 'species')->create();

    $this->actingAs($user)
        ->patch(route('plantings.update', [$garden, $planting]), validPlantingData([
            'plant_species_id' => $speciesB->id,
            'status' => PlantingStatus::Planned->value,
        ]))
        ->assertSessionHasErrors('plant_species_id');
});

// --- Variety-Konsistenz ---

test('variety from wrong species fails validation', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $speciesA = PlantSpecies::factory()->system()->create();
    $speciesB = PlantSpecies::factory()->system()->create();
    $variety = PlantVariety::factory()->system()->create(['plant_species_id' => $speciesB->id]);

    $this->actingAs($user)
        ->post(route('plantings.store', $garden), validPlantingData([
            'plant_species_id' => $speciesA->id,
            'plant_variety_id' => $variety->id,
        ]))
        ->assertSessionHasErrors('plant_variety_id');
});

test('species change with old mismatching variety fails validation', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $speciesA = PlantSpecies::factory()->system()->create();
    $speciesB = PlantSpecies::factory()->system()->create();
    $variety = PlantVariety::factory()->system()->create(['plant_species_id' => $speciesA->id]);
    $planting = GardenPlanting::factory()->for($garden)->for($speciesA, 'species')->create([
        'plant_variety_id' => $variety->id,
    ]);

    $this->actingAs($user)
        ->patch(route('plantings.update', [$garden, $planting]), [
            'plant_species_id' => $speciesB->id,
            'plant_variety_id' => $variety->id,
            'status' => PlantingStatus::Planned->value,
        ])
        ->assertSessionHasErrors('plant_variety_id');
});

test('species change with variety null is allowed', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $speciesA = PlantSpecies::factory()->system()->create();
    $speciesB = PlantSpecies::factory()->system()->create();
    $variety = PlantVariety::factory()->system()->create(['plant_species_id' => $speciesA->id]);
    $planting = GardenPlanting::factory()->for($garden)->for($speciesA, 'species')->create([
        'plant_variety_id' => $variety->id,
    ]);

    $this->actingAs($user)
        ->patch(route('plantings.update', [$garden, $planting]), [
            'plant_species_id' => $speciesB->id,
            'plant_variety_id' => null,
            'status' => PlantingStatus::Planned->value,
        ])
        ->assertRedirect();

    expect($planting->fresh()->plant_species_id)->toBe($speciesB->id);
    expect($planting->fresh()->plant_variety_id)->toBeNull();
});

test('foreign private variety is not in available species options', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $owner->id]);
    $species = PlantSpecies::factory()->system()->create();
    $privateVariety = PlantVariety::factory()->forUser($other)->create(['plant_species_id' => $species->id]);

    $this->actingAs($owner)
        ->get(route('plantings.create', $garden))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where(
            'availableSpecies',
            fn ($availableSpecies) => collect($availableSpecies)
                ->firstWhere('id', $species->id)['varieties'] === [],
        ));
});

test('rejected variety is not in available species options', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->system()->create();
    $rejectedVariety = PlantVariety::factory()->forUser($user)->rejected()->create(['plant_species_id' => $species->id]);

    $this->actingAs($user)
        ->get(route('plantings.create', $garden))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where(
            'availableSpecies',
            fn ($availableSpecies) => ! collect(
                collect($availableSpecies)->firstWhere('id', $species->id)['varieties'] ?? []
            )->pluck('id')->contains($rejectedVariety->id),
        ));
});

// --- Area-Regeln ---

test('archived area is rejected for new planting', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $area = Area::factory()->for($garden)->create(['archived_at' => now()]);
    $species = PlantSpecies::factory()->system()->create();

    $this->actingAs($user)
        ->post(route('plantings.store', $garden), validPlantingData([
            'plant_species_id' => $species->id,
            'area_id' => $area->id,
        ]))
        ->assertSessionHasErrors('area_id');
});

test('soft deleted area is rejected for new planting', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $area = Area::factory()->for($garden)->create();
    $area->delete();
    $species = PlantSpecies::factory()->system()->create();

    $this->actingAs($user)
        ->post(route('plantings.store', $garden), validPlantingData([
            'plant_species_id' => $species->id,
            'area_id' => $area->id,
        ]))
        ->assertSessionHasErrors('area_id');
});

test('area from different garden is rejected', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $other = Garden::factory()->create(['user_id' => $user->id]);
    $area = Area::factory()->for($other)->create();
    $species = PlantSpecies::factory()->system()->create();

    $this->actingAs($user)
        ->post(route('plantings.store', $garden), validPlantingData([
            'plant_species_id' => $species->id,
            'area_id' => $area->id,
        ]))
        ->assertSessionHasErrors('area_id');
});

test('nullable area_id is allowed', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->system()->create();

    $this->actingAs($user)
        ->post(route('plantings.store', $garden), validPlantingData([
            'plant_species_id' => $species->id,
            'area_id' => null,
        ]))
        ->assertRedirect(route('gardens.show', $garden));

    expect(GardenPlanting::where('garden_id', $garden->id)->first()->area_id)->toBeNull();
});

// --- Area-Update-Regeln ---

test('updating note with archived area does not fail', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $area = Area::factory()->for($garden)->create();
    $species = PlantSpecies::factory()->system()->create();
    $planting = GardenPlanting::factory()->for($garden)->for($species, 'species')->create(['area_id' => $area->id]);

    $area->update(['archived_at' => now()]);

    $this->actingAs($user)
        ->patch(route('plantings.update', [$garden, $planting]), [
            'plant_species_id' => $species->id,
            'area_id' => $area->id,
            'status' => PlantingStatus::Planned->value,
            'notes' => 'Notiz bei archivierter Area',
        ])
        ->assertRedirect();

    expect($planting->fresh()->notes)->toBe('Notiz bei archivierter Area');
});

test('changing to another archived area is blocked', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $area = Area::factory()->for($garden)->create();
    $archivedArea = Area::factory()->for($garden)->create(['archived_at' => now()]);
    $species = PlantSpecies::factory()->system()->create();
    $planting = GardenPlanting::factory()->for($garden)->for($species, 'species')->create(['area_id' => $area->id]);

    $this->actingAs($user)
        ->patch(route('plantings.update', [$garden, $planting]), [
            'plant_species_id' => $species->id,
            'area_id' => $archivedArea->id,
            'status' => PlantingStatus::Planned->value,
        ])
        ->assertSessionHasErrors('area_id');
});

test('setting area_id to null is always allowed', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $area = Area::factory()->for($garden)->create(['archived_at' => now()]);
    $species = PlantSpecies::factory()->system()->create();
    $planting = GardenPlanting::factory()->for($garden)->for($species, 'species')->create(['area_id' => $area->id]);

    $this->actingAs($user)
        ->patch(route('plantings.update', [$garden, $planting]), [
            'plant_species_id' => $species->id,
            'area_id' => null,
            'status' => PlantingStatus::Planned->value,
        ])
        ->assertRedirect();

    expect($planting->fresh()->area_id)->toBeNull();
});

// --- Mengen-Validierung ---

test('3 pieces is valid', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->system()->create();

    $this->actingAs($user)
        ->post(route('plantings.store', $garden), validPlantingData([
            'plant_species_id' => $species->id,
            'quantity' => 3,
            'quantity_unit' => PlantingUnit::Pieces->value,
        ]))
        ->assertRedirect();
});

test('0.5 pieces is not valid', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->system()->create();

    $this->actingAs($user)
        ->post(route('plantings.store', $garden), validPlantingData([
            'plant_species_id' => $species->id,
            'quantity' => 0.5,
            'quantity_unit' => PlantingUnit::Pieces->value,
        ]))
        ->assertSessionHasErrors('quantity');
});

test('0 pieces is not valid', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->system()->create();

    $this->actingAs($user)
        ->post(route('plantings.store', $garden), validPlantingData([
            'plant_species_id' => $species->id,
            'quantity' => 0,
            'quantity_unit' => PlantingUnit::Pieces->value,
        ]))
        ->assertSessionHasErrors('quantity');
});

test('0.5 square meters is valid', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->system()->create();

    $this->actingAs($user)
        ->post(route('plantings.store', $garden), validPlantingData([
            'plant_species_id' => $species->id,
            'quantity' => 0.5,
            'quantity_unit' => PlantingUnit::SquareMeters->value,
        ]))
        ->assertRedirect();
});

test('quantity without unit fails', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->system()->create();

    $this->actingAs($user)
        ->post(route('plantings.store', $garden), validPlantingData([
            'plant_species_id' => $species->id,
            'quantity' => 3,
            'quantity_unit' => null,
        ]))
        ->assertSessionHasErrors('quantity_unit');
});

test('unit without quantity fails', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->system()->create();

    $this->actingAs($user)
        ->post(route('plantings.store', $garden), validPlantingData([
            'plant_species_id' => $species->id,
            'quantity' => null,
            'quantity_unit' => PlantingUnit::Pieces->value,
        ]))
        ->assertSessionHasErrors('quantity');
});

// --- Garden/Area-Integration ---

test('garden show contains plantings prop', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->system()->create();
    GardenPlanting::factory()->for($garden)->for($species, 'species')->create();

    $this->actingAs($user)
        ->get(route('gardens.show', $garden))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('plantings')
            ->has('plantings.data', 1)
        );
});

test('garden show plantings does not contain soft-deleted plantings', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->system()->create();
    $planting = GardenPlanting::factory()->for($garden)->for($species, 'species')->create();
    $planting->delete();

    $this->actingAs($user)
        ->get(route('gardens.show', $garden))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('plantings.data', 0)
        );
});

test('area show contains plantings prop', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $area = Area::factory()->for($garden)->create();
    $species = PlantSpecies::factory()->system()->create();
    GardenPlanting::factory()->for($garden)->for($species, 'species')->create(['area_id' => $area->id]);

    $this->actingAs($user)
        ->get(route('areas.show', [$garden, $area]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('plantings')
            ->has('plantings.data', 1)
        );
});

// --- Delete-Guards (App-Ebene) ---

test('species with active planting cannot be soft deleted', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->forUser($user)->create();
    GardenPlanting::factory()->for($garden)->for($species, 'species')->create();

    expect(fn () => app(DeletePlantSpecies::class)->handle($species))
        ->toThrow(CatalogActionException::class);
});

test('species can be soft deleted after planting is deleted', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->forUser($user)->create();
    $planting = GardenPlanting::factory()->for($garden)->for($species, 'species')->create();
    $planting->delete();

    app(DeletePlantSpecies::class)->handle($species);
    expect($species->fresh()->deleted_at)->not->toBeNull();
});

test('variety with active planting cannot be soft deleted', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->system()->create();
    $variety = PlantVariety::factory()->forUser($user)->create(['plant_species_id' => $species->id]);
    GardenPlanting::factory()->for($garden)->for($species, 'species')->create(['plant_variety_id' => $variety->id]);

    expect(fn () => app(DeletePlantVariety::class)->handle($variety))
        ->toThrow(CatalogActionException::class);
});

test('variety can be soft deleted after planting is deleted', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->system()->create();
    $variety = PlantVariety::factory()->forUser($user)->create(['plant_species_id' => $species->id]);
    $planting = GardenPlanting::factory()->for($garden)->for($species, 'species')->create(['plant_variety_id' => $variety->id]);
    $planting->delete();

    app(DeletePlantVariety::class)->handle($variety);
    expect($variety->fresh()->deleted_at)->not->toBeNull();
});

test('area with active planting cannot be soft deleted', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $area = Area::factory()->for($garden)->create();
    $species = PlantSpecies::factory()->system()->create();
    GardenPlanting::factory()->for($garden)->for($species, 'species')->create(['area_id' => $area->id]);

    expect(fn () => app(DeleteArea::class)->handle($area))
        ->toThrow(ValidationException::class);
});

test('area can be soft deleted after planting is deleted', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $area = Area::factory()->for($garden)->create();
    $species = PlantSpecies::factory()->system()->create();
    $planting = GardenPlanting::factory()->for($garden)->for($species, 'species')->create(['area_id' => $area->id]);
    $planting->delete();

    app(DeleteArea::class)->handle($area);
    expect($area->fresh()->deleted_at)->not->toBeNull();
});

// --- Delete-Guards (DB-Ebene: MySQL RESTRICT und CASCADE) ---

test('force delete garden cascade deletes plantings', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->system()->create();
    $planting = GardenPlanting::factory()->for($garden)->for($species, 'species')->create();

    $garden->forceDelete();

    expect(GardenPlanting::withTrashed()->find($planting->id))->toBeNull();
});

test('force delete species with planting throws db error', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->system()->create();
    GardenPlanting::factory()->for($garden)->for($species, 'species')->create();

    expect(fn () => $species->forceDelete())
        ->toThrow(QueryException::class);
});

test('force delete variety with planting throws db error', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->system()->create();
    $variety = PlantVariety::factory()->system()->create(['plant_species_id' => $species->id]);
    GardenPlanting::factory()->for($garden)->for($species, 'species')->create(['plant_variety_id' => $variety->id]);

    expect(fn () => $variety->forceDelete())
        ->toThrow(QueryException::class);
});

// --- Actions defensiv ---

test('CreateGardenPlanting by non-owner throws authorization exception', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $owner->id]);
    $species = PlantSpecies::factory()->system()->create();

    expect(fn () => app(CreateGardenPlanting::class)->handle(
        $garden, $stranger, validPlantingData(['plant_species_id' => $species->id])
    ))->toThrow(AuthorizationException::class);
});

test('CreateGardenPlanting with rejected species throws CatalogActionException', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->forUser($user)->rejected()->create();

    expect(fn () => app(CreateGardenPlanting::class)->handle(
        $garden, $user, validPlantingData(['plant_species_id' => $species->id])
    ))->toThrow(CatalogActionException::class);
});

test('CreateGardenPlanting with foreign private species throws CatalogActionException', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $owner->id]);
    $species = PlantSpecies::factory()->forUser($other)->create();

    expect(fn () => app(CreateGardenPlanting::class)->handle(
        $garden, $owner, validPlantingData(['plant_species_id' => $species->id])
    ))->toThrow(CatalogActionException::class);
});

test('CreateGardenPlanting with mismatching variety throws CatalogActionException', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $speciesA = PlantSpecies::factory()->system()->create();
    $speciesB = PlantSpecies::factory()->system()->create();
    $variety = PlantVariety::factory()->system()->create(['plant_species_id' => $speciesB->id]);

    expect(fn () => app(CreateGardenPlanting::class)->handle(
        $garden, $user, validPlantingData([
            'plant_species_id' => $speciesA->id,
            'plant_variety_id' => $variety->id,
        ])
    ))->toThrow(CatalogActionException::class);
});

test('UpdateGardenPlanting by non-owner throws authorization exception', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $owner->id]);
    $species = PlantSpecies::factory()->system()->create();
    $planting = GardenPlanting::factory()->for($garden)->for($species, 'species')->create();

    expect(fn () => app(UpdateGardenPlanting::class)->handle(
        $planting, $garden, $stranger, validPlantingData(['plant_species_id' => $species->id])
    ))->toThrow(AuthorizationException::class);
});

// --- Mass-Assignment ---

test('garden_id in payload is ignored and planting lands in correct garden', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $otherGarden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->system()->create();

    $this->actingAs($user)
        ->post(route('plantings.store', $garden), array_merge(
            validPlantingData(['plant_species_id' => $species->id]),
            ['garden_id' => $otherGarden->id]
        ))
        ->assertRedirect();

    $planting = GardenPlanting::where('plant_species_id', $species->id)->first();
    expect($planting->garden_id)->toBe($garden->id);
});

test('removed_at in payload is ignored', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->system()->create();

    $this->actingAs($user)
        ->post(route('plantings.store', $garden), array_merge(
            validPlantingData(['plant_species_id' => $species->id]),
            ['removed_at' => '2020-01-01 00:00:00']
        ))
        ->assertRedirect();

    $planting = GardenPlanting::where('plant_species_id', $species->id)->first();
    expect($planting->removed_at)->toBeNull();
});

// --- ?area Query-Parameter Validierung ---

test('valid area preselects correctly', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $area = Area::factory()->for($garden)->create();

    $this->actingAs($user)
        ->get(route('plantings.create', $garden).'?area='.$area->id)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('preselectedAreaId', $area->id)
        );
});

test('foreign area in query param yields null preselectedAreaId', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $owner->id]);
    $foreignArea = Area::factory()->for(Garden::factory()->create(['user_id' => $stranger->id]))->create();

    $this->actingAs($owner)
        ->get(route('plantings.create', $garden).'?area='.$foreignArea->id)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('preselectedAreaId', null)
        );
});

test('archived area in query param yields null preselectedAreaId', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $area = Area::factory()->for($garden)->create(['archived_at' => now()]);

    $this->actingAs($user)
        ->get(route('plantings.create', $garden).'?area='.$area->id)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('preselectedAreaId', null)
        );
});

// --- Darstellung archivierter Referenzen ---

test('planting with archived species is displayable', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->system()->create();
    $planting = GardenPlanting::factory()->for($garden)->for($species, 'species')->create();

    $species->update(['archived_at' => now()]);

    $this->actingAs($user)
        ->get(route('plantings.show', [$garden, $planting]))
        ->assertOk();
});

test('planting with archived variety is displayable', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $species = PlantSpecies::factory()->system()->create();
    $variety = PlantVariety::factory()->system()->create(['plant_species_id' => $species->id]);
    $planting = GardenPlanting::factory()->for($garden)->for($species, 'species')->create(['plant_variety_id' => $variety->id]);

    $variety->update(['archived_at' => now()]);

    $this->actingAs($user)
        ->get(route('plantings.show', [$garden, $planting]))
        ->assertOk();
});

// --- Formular-Props (kein Datenleck) ---

test('available species options do not contain moderation data', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    PlantSpecies::factory()->system()->create();

    $this->actingAs($user)
        ->get(route('plantings.create', $garden))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where(
            'availableSpecies',
            fn ($availableSpecies) => collect($availableSpecies)->every(fn ($s) => array_key_exists('id', $s)
                && array_key_exists('common_name', $s)
                && array_key_exists('botanical_name', $s)
                && array_key_exists('origin', $s)
                && array_key_exists('catalog_status', $s)
                && array_key_exists('varieties', $s)
                && ! array_key_exists('moderation_note', $s)
                && ! array_key_exists('reviewed_by', $s)
                && ! array_key_exists('submitted_at', $s)
            ),
        ));
});
