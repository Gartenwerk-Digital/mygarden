<?php

use App\Enums\GardenVisibility;
use App\Models\Garden;
use App\Models\User;

function validGardenData(array $overrides = []): array
{
    return array_merge([
        'name' => 'Mein Garten',
        'description' => null,
        'visibility' => GardenVisibility::Private->value,
        'size_sqm' => null,
        'country' => null,
        'region' => null,
    ], $overrides);
}

// --- Zugriff und Authentifizierung ---

test('unauthenticated user is redirected from index', function () {
    $this->get(route('gardens.index'))->assertRedirect(route('login'));
});

test('unauthenticated user is redirected from create', function () {
    $this->get(route('gardens.create'))->assertRedirect(route('login'));
});

test('unauthenticated user cannot store garden', function () {
    $this->post(route('gardens.store'), validGardenData())->assertRedirect(route('login'));
});

// --- Index ---

test('index shows own active gardens only', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $own = Garden::factory()->create(['user_id' => $user->id, 'name' => 'Eigener Garten']);
    Garden::factory()->create(['user_id' => $other->id, 'name' => 'Fremder Garten']);

    $this->actingAs($user)
        ->get(route('gardens.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('gardens/index')
            ->has('activeGardens', 1)
            ->where('activeGardens.0.id', $own->id)
        );
});

test('archived gardens appear in archivedGardens not in activeGardens', function () {
    $user = User::factory()->create();
    Garden::factory()->create(['user_id' => $user->id]);
    Garden::factory()->archived()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('gardens.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('activeGardens', 1)
            ->has('archivedGardens', 1)
        );
});

// --- Policy: fremde Gärten ---

test('show: fremder kann privaten Garten nicht sehen', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($other)
        ->get(route('gardens.show', $garden))
        ->assertForbidden();
});

test('show: fremder kann öffentlichen Garten nicht sehen (Phase 3)', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $garden = Garden::factory()->public()->create(['user_id' => $owner->id]);

    $this->actingAs($other)
        ->get(route('gardens.show', $garden))
        ->assertForbidden();
});

test('show: fremder kann unlisted Garten nicht sehen (Phase 3)', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $owner->id, 'visibility' => GardenVisibility::Unlisted]);

    $this->actingAs($other)
        ->get(route('gardens.show', $garden))
        ->assertForbidden();
});

test('fremder kann edit nicht öffnen', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($other)
        ->get(route('gardens.edit', $garden))
        ->assertForbidden();
});

test('fremder kann update nicht ausführen', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($other)
        ->patch(route('gardens.update', $garden), validGardenData())
        ->assertForbidden();
});

test('fremder kann archive nicht ausführen', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($other)
        ->patch(route('gardens.archive', $garden))
        ->assertForbidden();
});

test('fremder kann unarchive nicht ausführen', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $garden = Garden::factory()->archived()->create(['user_id' => $owner->id]);

    $this->actingAs($other)
        ->patch(route('gardens.unarchive', $garden))
        ->assertForbidden();
});

test('fremder kann destroy nicht ausführen', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($other)
        ->delete(route('gardens.destroy', $garden))
        ->assertForbidden();
});

// --- Erstellen ---

test('create page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('gardens.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('gardens/create'));
});

test('garten kann erstellt werden', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('gardens.store'), validGardenData(['name' => 'Neuer Garten']))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('gardens.index'));

    expect($user->gardens()->where('name', 'Neuer Garten')->exists())->toBeTrue();
});

test('name ist pflichtfeld', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('gardens.store'), validGardenData(['name' => '']))
        ->assertSessionHasErrors('name');
});

test('ungültige visibility wird abgelehnt', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('gardens.store'), validGardenData(['visibility' => 'invalid']))
        ->assertSessionHasErrors('visibility');
});

test('ungültiger country-code wird abgelehnt', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('gardens.store'), validGardenData(['country' => 'XX']))
        ->assertSessionHasErrors('country');
});

test('country wird uppercase gespeichert', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('gardens.store'), validGardenData(['country' => 'de']))
        ->assertSessionHasNoErrors();

    expect($user->gardens()->first()->country)->toBe('DE');
});

test('name wird getrimmt', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('gardens.store'), validGardenData(['name' => '  Garten  ']))
        ->assertSessionHasNoErrors();

    expect($user->gardens()->first()->name)->toBe('Garten');
});

test('leere beschreibung wird null', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('gardens.store'), validGardenData(['description' => '']))
        ->assertSessionHasNoErrors();

    expect($user->gardens()->first()->description)->toBeNull();
});

test('size_sqm 0 wird abgelehnt', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('gardens.store'), validGardenData(['size_sqm' => 0]))
        ->assertSessionHasErrors('size_sqm');
});

test('size_sqm negativ wird abgelehnt', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('gardens.store'), validGardenData(['size_sqm' => -1]))
        ->assertSessionHasErrors('size_sqm');
});

test('size_sqm 0.01 ist gültig', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('gardens.store'), validGardenData(['size_sqm' => 0.01]))
        ->assertSessionHasNoErrors();
});

// --- Show ---

test('eigentümer kann garten sehen', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('gardens.show', $garden))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('gardens/show')
            ->where('garden.id', $garden->id)
        );
});

// --- Bearbeiten / Aktualisieren ---

test('edit page is displayed', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('gardens.edit', $garden))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('gardens/edit'));
});

test('garten kann aktualisiert werden', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->patch(route('gardens.update', $garden), validGardenData([
            'name' => 'Aktualisierter Garten',
            'visibility' => GardenVisibility::Public->value,
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('gardens.show', $garden));

    expect($garden->fresh()->name)->toBe('Aktualisierter Garten');
    expect($garden->fresh()->visibility)->toBe(GardenVisibility::Public);
});

// --- Archivieren / Unarchivieren ---

test('archive setzt archived_at', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->patch(route('gardens.archive', $garden))
        ->assertRedirect(route('gardens.show', $garden));

    expect($garden->fresh()->archived_at)->not->toBeNull();
});

test('archive auf bereits archivierten garten ändert timestamp nicht', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->archived()->create(['user_id' => $user->id]);

    $originalTimestamp = $garden->archived_at;

    $this->actingAs($user)
        ->patch(route('gardens.archive', $garden));

    expect($garden->fresh()->archived_at->toDateTimeString())
        ->toBe($originalTimestamp->toDateTimeString());
});

test('unarchive entfernt archived_at', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->archived()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->patch(route('gardens.unarchive', $garden))
        ->assertRedirect(route('gardens.show', $garden));

    expect($garden->fresh()->archived_at)->toBeNull();
});

test('archivierter garten bleibt für eigentümer sichtbar', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->archived()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('gardens.show', $garden))
        ->assertOk();
});

// --- Löschen ---

test('garten kann gelöscht werden (soft delete)', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->delete(route('gardens.destroy', $garden))
        ->assertRedirect(route('gardens.index'));

    expect(Garden::find($garden->id))->toBeNull();
    expect(Garden::withTrashed()->find($garden->id))->not->toBeNull();
});

test('gelöschter garten erscheint nicht im index', function () {
    $user = User::factory()->create();
    Garden::factory()->create(['user_id' => $user->id]);
    $deleted = Garden::factory()->create(['user_id' => $user->id]);
    $deleted->delete();

    $this->actingAs($user)
        ->get(route('gardens.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('activeGardens', 1));
});

test('gelöschter garten gibt 404 bei direktem aufruf', function () {
    $user = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $user->id]);
    $garden->delete();

    $this->actingAs($user)
        ->get(route('gardens.show', $garden))
        ->assertNotFound();
});

// --- Beziehungen ---

test('user hat gardens beziehung', function () {
    $user = User::factory()->create();
    Garden::factory()->count(3)->create(['user_id' => $user->id]);

    expect($user->gardens)->toHaveCount(3);
    expect($user->gardens->first())->toBeInstanceOf(Garden::class);
});
