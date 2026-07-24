<?php

use App\Actions\Gardens\ForceDeleteGarden;
use App\Actions\PlantingEntries\CreatePlantingEntry;
use App\Actions\PlantingEntries\ForceDeleteGardenPlanting;
use App\Enums\EntryType;
use App\Models\Garden;
use App\Models\GardenPlanting;
use App\Models\PlantingEntry;
use App\Models\PlantingEntryImage;
use App\Models\PlantSpecies;
use App\Models\User;
use App\Support\ImageStorage;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// ─────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────

function makePlantingWithOwner(): array
{
    $owner = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $owner->id]);
    $species = PlantSpecies::factory()->system()->create();
    $planting = GardenPlanting::factory()->for($garden)->for($species, 'species')->create();

    return [$owner, $garden, $planting];
}

function fakeJpeg(string $name = 'photo.jpg', int $kb = 100): UploadedFile
{
    return UploadedFile::fake()->image($name, 100, 100)->size($kb);
}

function validEntryData(array $overrides = []): array
{
    return array_merge([
        'entry_type' => 'observation',
        'notes' => 'Test-Beobachtung',
        'occurred_at' => now()->toDateString(),
    ], $overrides);
}

// ─────────────────────────────────────────────
// 1. Authentifizierung
// ─────────────────────────────────────────────

test('unauthenticated user is redirected from entry create', function () {
    [$owner, $garden, $planting] = makePlantingWithOwner();

    $this->get(route('planting-entries.create', [$garden, $planting]))
        ->assertRedirect(route('login'));
});

test('unauthenticated user cannot store entry', function () {
    [$owner, $garden, $planting] = makePlantingWithOwner();

    $this->post(route('planting-entries.store', [$garden, $planting]), validEntryData())
        ->assertRedirect(route('login'));
});

test('unauthenticated user is redirected from entry edit', function () {
    [$owner, $garden, $planting] = makePlantingWithOwner();
    $entry = PlantingEntry::factory()->for($planting, 'planting')->create();

    $this->get(route('planting-entries.edit', [$garden, $planting, $entry]))
        ->assertRedirect(route('login'));
});

// ─────────────────────────────────────────────
// 2. Autorisierung
// ─────────────────────────────────────────────

test('owner can create entry', function () {
    [$owner, $garden, $planting] = makePlantingWithOwner();

    $this->actingAs($owner)
        ->post(route('planting-entries.store', [$garden, $planting]), validEntryData())
        ->assertRedirect(route('plantings.show', [$garden, $planting]));
});

test('stranger cannot create entry', function () {
    [$owner, $garden, $planting] = makePlantingWithOwner();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->post(route('planting-entries.store', [$garden, $planting]), validEntryData())
        ->assertForbidden();
});

test('stranger cannot edit entry', function () {
    [$owner, $garden, $planting] = makePlantingWithOwner();
    $stranger = User::factory()->create();
    $entry = PlantingEntry::factory()->for($planting, 'planting')->create();

    $this->actingAs($stranger)
        ->get(route('planting-entries.edit', [$garden, $planting, $entry]))
        ->assertForbidden();
});

test('stranger cannot delete entry', function () {
    [$owner, $garden, $planting] = makePlantingWithOwner();
    $stranger = User::factory()->create();
    $entry = PlantingEntry::factory()->for($planting, 'planting')->create();

    $this->actingAs($stranger)
        ->delete(route('planting-entries.destroy', [$garden, $planting, $entry]))
        ->assertForbidden();
});

test('direct CreatePlantingEntry action call by non-owner throws AuthorizationException', function () {
    [$owner, $garden, $planting] = makePlantingWithOwner();
    $stranger = User::factory()->create();

    expect(fn () => (new CreatePlantingEntry(new ImageStorage))->handle($planting, $stranger, validEntryData(), []))
        ->toThrow(AuthorizationException::class);
});

// ─────────────────────────────────────────────
// 3. ScopeBindings
// ─────────────────────────────────────────────

test('entry belonging to different planting returns 404', function () {
    [$owner, $garden, $planting] = makePlantingWithOwner();
    $otherPlanting = GardenPlanting::factory()->for($garden)->for(PlantSpecies::factory()->system(), 'species')->create();
    $entry = PlantingEntry::factory()->for($otherPlanting, 'planting')->create();

    $this->actingAs($owner)
        ->get(route('planting-entries.edit', [$garden, $planting, $entry]))
        ->assertNotFound();
});

test('image belonging to different entry returns 404 on image show', function () {
    Storage::fake('local');
    [$owner, $garden, $planting] = makePlantingWithOwner();
    $entry = PlantingEntry::factory()->for($planting, 'planting')->create();
    $otherEntry = PlantingEntry::factory()->for($planting, 'planting')->create();

    Storage::disk('local')->put('planting-entries/1/test.jpg', 'fake');
    $image = PlantingEntryImage::factory()->for($otherEntry, 'entry')->create([
        'path' => 'planting-entries/1/test.jpg',
    ]);

    $this->actingAs($owner)
        ->get(route('planting-entry-images.show', [$garden, $planting, $entry, $image]))
        ->assertNotFound();
});

test('soft-deleted planting returns 404 on entry create', function () {
    [$owner, $garden, $planting] = makePlantingWithOwner();
    $planting->delete();

    $this->actingAs($owner)
        ->get(route('planting-entries.create', [$garden, $planting]))
        ->assertNotFound();
});

// ─────────────────────────────────────────────
// 4. CRUD
// ─────────────────────────────────────────────

test('owner can store entry', function () {
    [$owner, $garden, $planting] = makePlantingWithOwner();

    $this->actingAs($owner)
        ->post(route('planting-entries.store', [$garden, $planting]), validEntryData())
        ->assertRedirect(route('plantings.show', [$garden, $planting]));

    expect($planting->entries()->count())->toBe(1);
});

test('owner can update entry', function () {
    [$owner, $garden, $planting] = makePlantingWithOwner();
    $entry = PlantingEntry::factory()->for($planting, 'planting')->create();

    $this->actingAs($owner)
        ->patch(route('planting-entries.update', [$garden, $planting, $entry]), [
            'entry_type' => 'care',
            'notes' => 'Gedüngt',
            'occurred_at' => now()->toDateString(),
        ])
        ->assertRedirect(route('plantings.show', [$garden, $planting]));

    expect($entry->fresh()->entry_type)->toBe(EntryType::Care);
});

test('owner can delete entry', function () {
    [$owner, $garden, $planting] = makePlantingWithOwner();
    $entry = PlantingEntry::factory()->for($planting, 'planting')->create();

    $this->actingAs($owner)
        ->delete(route('planting-entries.destroy', [$garden, $planting, $entry]))
        ->assertRedirect(route('plantings.show', [$garden, $planting]));

    expect(PlantingEntry::find($entry->id))->toBeNull();
});

// ─────────────────────────────────────────────
// 5. EntryType-Validierung
// ─────────────────────────────────────────────

test('invalid entry_type is rejected', function () {
    [$owner, $garden, $planting] = makePlantingWithOwner();

    $this->actingAs($owner)
        ->post(route('planting-entries.store', [$garden, $planting]), validEntryData(['entry_type' => 'harvest']))
        ->assertSessionHasErrors('entry_type');
});

// ─────────────────────────────────────────────
// 6. occurred_at
// ─────────────────────────────────────────────

test('occurred_at is required', function () {
    [$owner, $garden, $planting] = makePlantingWithOwner();

    $this->actingAs($owner)
        ->post(route('planting-entries.store', [$garden, $planting]), validEntryData(['occurred_at' => '']))
        ->assertSessionHasErrors('occurred_at');
});

test('invalid occurred_at date is rejected', function () {
    [$owner, $garden, $planting] = makePlantingWithOwner();

    $this->actingAs($owner)
        ->post(route('planting-entries.store', [$garden, $planting]), validEntryData(['occurred_at' => 'not-a-date']))
        ->assertSessionHasErrors('occurred_at');
});

// ─────────────────────────────────────────────
// 7. Minimalinhalt Store
// ─────────────────────────────────────────────

test('entry with only notes is allowed', function () {
    [$owner, $garden, $planting] = makePlantingWithOwner();

    $this->actingAs($owner)
        ->post(route('planting-entries.store', [$garden, $planting]), validEntryData(['notes' => 'Nur eine Notiz']))
        ->assertRedirect();

    expect($planting->entries()->count())->toBe(1);
});

test('entry with only image is allowed', function () {
    Storage::fake('local');
    [$owner, $garden, $planting] = makePlantingWithOwner();

    $this->actingAs($owner)
        ->post(route('planting-entries.store', [$garden, $planting]), array_merge(
            validEntryData(['notes' => null]),
            ['images' => [fakeJpeg()]]
        ))
        ->assertRedirect();

    expect($planting->entries()->count())->toBe(1);
});

test('entry without notes and images is rejected', function () {
    [$owner, $garden, $planting] = makePlantingWithOwner();

    $this->actingAs($owner)
        ->post(route('planting-entries.store', [$garden, $planting]), validEntryData(['notes' => null]))
        ->assertSessionHasErrors('notes');
});

// ─────────────────────────────────────────────
// 8. Minimalinhalt Update
// ─────────────────────────────────────────────

test('last image can be removed if notes remain', function () {
    Storage::fake('local');
    [$owner, $garden, $planting] = makePlantingWithOwner();
    $entry = PlantingEntry::factory()->for($planting, 'planting')->create(['notes' => 'Notiz bleibt']);
    Storage::disk('local')->put("planting-entries/{$entry->id}/img.jpg", 'x');
    $image = PlantingEntryImage::factory()->for($entry, 'entry')->create([
        'path' => "planting-entries/{$entry->id}/img.jpg",
    ]);

    $this->actingAs($owner)
        ->patch(route('planting-entries.update', [$garden, $planting, $entry]), [
            'entry_type' => 'observation',
            'notes' => 'Notiz bleibt',
            'occurred_at' => now()->toDateString(),
            'remove_image_ids' => [$image->id],
        ])
        ->assertRedirect();

    expect(PlantingEntryImage::find($image->id))->toBeNull();
});

test('last note can be removed if image remains', function () {
    Storage::fake('local');
    [$owner, $garden, $planting] = makePlantingWithOwner();
    $entry = PlantingEntry::factory()->for($planting, 'planting')->create(['notes' => 'Wird entfernt']);
    Storage::disk('local')->put("planting-entries/{$entry->id}/img.jpg", 'x');
    PlantingEntryImage::factory()->for($entry, 'entry')->create([
        'path' => "planting-entries/{$entry->id}/img.jpg",
    ]);

    $this->actingAs($owner)
        ->patch(route('planting-entries.update', [$garden, $planting, $entry]), [
            'entry_type' => 'observation',
            'notes' => '',
            'occurred_at' => now()->toDateString(),
        ])
        ->assertRedirect();

    expect($entry->fresh()->notes)->toBeNull();
});

test('update rejected when notes empty and all images removed', function () {
    Storage::fake('local');
    [$owner, $garden, $planting] = makePlantingWithOwner();
    $entry = PlantingEntry::factory()->for($planting, 'planting')->create(['notes' => 'Notiz']);
    Storage::disk('local')->put("planting-entries/{$entry->id}/img.jpg", 'x');
    $image = PlantingEntryImage::factory()->for($entry, 'entry')->create([
        'path' => "planting-entries/{$entry->id}/img.jpg",
    ]);

    $this->actingAs($owner)
        ->patch(route('planting-entries.update', [$garden, $planting, $entry]), [
            'entry_type' => 'observation',
            'notes' => '',
            'occurred_at' => now()->toDateString(),
            'remove_image_ids' => [$image->id],
        ])
        ->assertSessionHasErrors('notes');
});

test('entry can be deleted even without remaining content', function () {
    [$owner, $garden, $planting] = makePlantingWithOwner();
    $entry = PlantingEntry::factory()->for($planting, 'planting')->withoutNotes()->create();

    $this->actingAs($owner)
        ->delete(route('planting-entries.destroy', [$garden, $planting, $entry]))
        ->assertRedirect();

    expect(PlantingEntry::find($entry->id))->toBeNull();
});

// ─────────────────────────────────────────────
// 9. Bild-Upload
// ─────────────────────────────────────────────

test('image is stored on disk and recorded in db', function () {
    Storage::fake('local');
    [$owner, $garden, $planting] = makePlantingWithOwner();

    $this->actingAs($owner)
        ->post(route('planting-entries.store', [$garden, $planting]), array_merge(
            validEntryData(),
            ['images' => [fakeJpeg()]]
        ))
        ->assertRedirect();

    $entry = $planting->entries()->first();
    expect($entry->images()->count())->toBe(1);

    $image = $entry->images()->first();
    Storage::disk('local')->assertExists($image->path);
    expect($image->mime_type)->toBe('image/jpeg');
});

// ─────────────────────────────────────────────
// 10. Upload-Limits
// ─────────────────────────────────────────────

test('invalid file type is rejected', function () {
    [$owner, $garden, $planting] = makePlantingWithOwner();

    $this->actingAs($owner)
        ->post(route('planting-entries.store', [$garden, $planting]), array_merge(
            validEntryData(),
            ['images' => [UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf')]]
        ))
        ->assertSessionHasErrors('images.0');
});

test('file exceeding 3 MB is rejected', function () {
    [$owner, $garden, $planting] = makePlantingWithOwner();

    $this->actingAs($owner)
        ->post(route('planting-entries.store', [$garden, $planting]), array_merge(
            validEntryData(),
            ['images' => [fakeJpeg('big.jpg', 4096)]]
        ))
        ->assertSessionHasErrors('images.0');
});

test('more than 5 files per request is rejected', function () {
    [$owner, $garden, $planting] = makePlantingWithOwner();

    $files = array_fill(0, 6, fakeJpeg());

    $this->actingAs($owner)
        ->post(route('planting-entries.store', [$garden, $planting]), array_merge(
            validEntryData(),
            ['images' => $files]
        ))
        ->assertSessionHasErrors('images');
});

test('adding images beyond 10 per entry is rejected', function () {
    Storage::fake('local');
    [$owner, $garden, $planting] = makePlantingWithOwner();
    $entry = PlantingEntry::factory()->for($planting, 'planting')->create();

    for ($i = 0; $i < 10; $i++) {
        Storage::disk('local')->put("planting-entries/{$entry->id}/img{$i}.jpg", 'x');
        PlantingEntryImage::factory()->for($entry, 'entry')->create([
            'path' => "planting-entries/{$entry->id}/img{$i}.jpg",
            'sort_order' => $i,
        ]);
    }

    $this->actingAs($owner)
        ->patch(route('planting-entries.update', [$garden, $planting, $entry]), array_merge(
            validEntryData(),
            ['images' => [fakeJpeg()]]
        ))
        ->assertSessionHasErrors('images');
});

// ─────────────────────────────────────────────
// 11. remove_image_ids
// ─────────────────────────────────────────────

test('remove_image_id from different entry is rejected', function () {
    [$owner, $garden, $planting] = makePlantingWithOwner();
    $entry = PlantingEntry::factory()->for($planting, 'planting')->create();
    $otherEntry = PlantingEntry::factory()->for($planting, 'planting')->create();
    $otherImage = PlantingEntryImage::factory()->for($otherEntry, 'entry')->create();

    $this->actingAs($owner)
        ->patch(route('planting-entries.update', [$garden, $planting, $entry]), array_merge(
            validEntryData(),
            ['remove_image_ids' => [$otherImage->id]]
        ))
        ->assertSessionHasErrors('remove_image_ids.0');
});

test('duplicate remove_image_id is rejected by distinct rule', function () {
    Storage::fake('local');
    [$owner, $garden, $planting] = makePlantingWithOwner();
    $entry = PlantingEntry::factory()->for($planting, 'planting')->create();
    Storage::disk('local')->put("planting-entries/{$entry->id}/img.jpg", 'x');
    $image = PlantingEntryImage::factory()->for($entry, 'entry')->create([
        'path' => "planting-entries/{$entry->id}/img.jpg",
    ]);

    $this->actingAs($owner)
        ->patch(route('planting-entries.update', [$garden, $planting, $entry]), array_merge(
            validEntryData(),
            ['remove_image_ids' => [$image->id, $image->id]]
        ))
        ->assertSessionHasErrors('remove_image_ids.1');
});

// ─────────────────────────────────────────────
// 12. Gesamtlimit
// ─────────────────────────────────────────────

test('exactly 10 total images is allowed', function () {
    Storage::fake('local');
    [$owner, $garden, $planting] = makePlantingWithOwner();
    $entry = PlantingEntry::factory()->for($planting, 'planting')->create();

    for ($i = 0; $i < 9; $i++) {
        Storage::disk('local')->put("planting-entries/{$entry->id}/img{$i}.jpg", 'x');
        PlantingEntryImage::factory()->for($entry, 'entry')->create([
            'path' => "planting-entries/{$entry->id}/img{$i}.jpg",
            'sort_order' => $i,
        ]);
    }

    $this->actingAs($owner)
        ->patch(route('planting-entries.update', [$garden, $planting, $entry]), array_merge(
            validEntryData(),
            ['images' => [fakeJpeg()]]
        ))
        ->assertRedirect();

    expect($entry->images()->count())->toBe(10);
});

test('remove and add images keeping total at or below 10 is allowed', function () {
    Storage::fake('local');
    [$owner, $garden, $planting] = makePlantingWithOwner();
    $entry = PlantingEntry::factory()->for($planting, 'planting')->create();

    $imageIds = [];
    for ($i = 0; $i < 10; $i++) {
        Storage::disk('local')->put("planting-entries/{$entry->id}/img{$i}.jpg", 'x');
        $img = PlantingEntryImage::factory()->for($entry, 'entry')->create([
            'path' => "planting-entries/{$entry->id}/img{$i}.jpg",
            'sort_order' => $i,
        ]);
        $imageIds[] = $img->id;
    }

    // Remove 2, add 2 → stays at 10
    $this->actingAs($owner)
        ->patch(route('planting-entries.update', [$garden, $planting, $entry]), array_merge(
            validEntryData(),
            [
                'remove_image_ids' => [$imageIds[0], $imageIds[1]],
                'images' => [fakeJpeg(), fakeJpeg()],
            ]
        ))
        ->assertRedirect();

    expect($entry->images()->count())->toBe(10);
});

// ─────────────────────────────────────────────
// 15. Cleanup Delete
// ─────────────────────────────────────────────

test('entry images are deleted from storage after entry delete', function () {
    Storage::fake('local');
    [$owner, $garden, $planting] = makePlantingWithOwner();
    $entry = PlantingEntry::factory()->for($planting, 'planting')->create();
    Storage::disk('local')->put("planting-entries/{$entry->id}/img.jpg", 'x');
    PlantingEntryImage::factory()->for($entry, 'entry')->create([
        'path' => "planting-entries/{$entry->id}/img.jpg",
    ]);

    $this->actingAs($owner)
        ->delete(route('planting-entries.destroy', [$garden, $planting, $entry]))
        ->assertRedirect();

    Storage::disk('local')->assertMissing("planting-entries/{$entry->id}/img.jpg");
    expect(PlantingEntry::find($entry->id))->toBeNull();
});

test('storage error after successful db delete does not return error response', function () {
    Storage::fake('local');
    [$owner, $garden, $planting] = makePlantingWithOwner();
    $entry = PlantingEntry::factory()->for($planting, 'planting')->create(['notes' => 'test']);
    // No actual file – Storage::delete returns false silently
    PlantingEntryImage::factory()->for($entry, 'entry')->create([
        'path' => 'nonexistent/file.jpg',
    ]);

    $this->actingAs($owner)
        ->delete(route('planting-entries.destroy', [$garden, $planting, $entry]))
        ->assertRedirect();

    expect(PlantingEntry::find($entry->id))->toBeNull();
});

// ─────────────────────────────────────────────
// 16. Cleanup Update
// ─────────────────────────────────────────────

test('old image files are deleted after successful update', function () {
    Storage::fake('local');
    [$owner, $garden, $planting] = makePlantingWithOwner();
    $entry = PlantingEntry::factory()->for($planting, 'planting')->create();
    Storage::disk('local')->put("planting-entries/{$entry->id}/old.jpg", 'x');
    $image = PlantingEntryImage::factory()->for($entry, 'entry')->create([
        'path' => "planting-entries/{$entry->id}/old.jpg",
    ]);

    $this->actingAs($owner)
        ->patch(route('planting-entries.update', [$garden, $planting, $entry]), [
            'entry_type' => 'observation',
            'notes' => 'Updated',
            'occurred_at' => now()->toDateString(),
            'remove_image_ids' => [$image->id],
        ])
        ->assertRedirect();

    Storage::disk('local')->assertMissing("planting-entries/{$entry->id}/old.jpg");
    expect(PlantingEntryImage::find($image->id))->toBeNull();
});

// ─────────────────────────────────────────────
// 17. ForceDeleteGardenPlanting
// ─────────────────────────────────────────────

test('ForceDeleteGardenPlanting removes entries, images and files', function () {
    Storage::fake('local');
    [$owner, $garden, $planting] = makePlantingWithOwner();
    $entry = PlantingEntry::factory()->for($planting, 'planting')->create();
    Storage::disk('local')->put("planting-entries/{$entry->id}/img.jpg", 'x');
    PlantingEntryImage::factory()->for($entry, 'entry')->create([
        'path' => "planting-entries/{$entry->id}/img.jpg",
    ]);

    (new ForceDeleteGardenPlanting(new ImageStorage))->handle($planting, $owner);

    expect(PlantingEntry::find($entry->id))->toBeNull();
    expect(GardenPlanting::withTrashed()->find($planting->id))->toBeNull();
    Storage::disk('local')->assertMissing("planting-entries/{$entry->id}/img.jpg");
});

test('ForceDeleteGardenPlanting by non-owner throws AuthorizationException', function () {
    [$owner, $garden, $planting] = makePlantingWithOwner();
    $stranger = User::factory()->create();

    expect(fn () => (new ForceDeleteGardenPlanting(new ImageStorage))->handle($planting, $stranger))
        ->toThrow(AuthorizationException::class);
});

// ─────────────────────────────────────────────
// 18. ForceDeleteGarden
// ─────────────────────────────────────────────

test('ForceDeleteGarden removes all timeline files', function () {
    Storage::fake('local');
    [$owner, $garden, $planting] = makePlantingWithOwner();
    $entry = PlantingEntry::factory()->for($planting, 'planting')->create();
    Storage::disk('local')->put("planting-entries/{$entry->id}/img.jpg", 'x');
    PlantingEntryImage::factory()->for($entry, 'entry')->create([
        'path' => "planting-entries/{$entry->id}/img.jpg",
    ]);

    (new ForceDeleteGarden(new ImageStorage))->handle($garden, $owner);

    expect(Garden::withTrashed()->find($garden->id))->toBeNull();
    Storage::disk('local')->assertMissing("planting-entries/{$entry->id}/img.jpg");
});

test('ForceDeleteGarden by non-owner throws AuthorizationException', function () {
    [$owner, $garden, $planting] = makePlantingWithOwner();
    $stranger = User::factory()->create();

    expect(fn () => (new ForceDeleteGarden(new ImageStorage))->handle($garden, $stranger))
        ->toThrow(AuthorizationException::class);
});

// ─────────────────────────────────────────────
// 19. Soft Delete GardenPlanting
// ─────────────────────────────────────────────

test('soft delete of GardenPlanting keeps entries and files', function () {
    Storage::fake('local');
    [$owner, $garden, $planting] = makePlantingWithOwner();
    $entry = PlantingEntry::factory()->for($planting, 'planting')->create();
    Storage::disk('local')->put("planting-entries/{$entry->id}/img.jpg", 'x');
    PlantingEntryImage::factory()->for($entry, 'entry')->create([
        'path' => "planting-entries/{$entry->id}/img.jpg",
    ]);

    $planting->delete();

    expect(PlantingEntry::find($entry->id))->not->toBeNull();
    Storage::disk('local')->assertExists("planting-entries/{$entry->id}/img.jpg");
});

// ─────────────────────────────────────────────
// 20. Soft Delete Garden
// ─────────────────────────────────────────────

test('soft delete of Garden keeps entries and files', function () {
    Storage::fake('local');
    [$owner, $garden, $planting] = makePlantingWithOwner();
    $entry = PlantingEntry::factory()->for($planting, 'planting')->create();
    Storage::disk('local')->put("planting-entries/{$entry->id}/img.jpg", 'x');
    PlantingEntryImage::factory()->for($entry, 'entry')->create([
        'path' => "planting-entries/{$entry->id}/img.jpg",
    ]);

    $garden->delete();

    expect(PlantingEntry::find($entry->id))->not->toBeNull();
    Storage::disk('local')->assertExists("planting-entries/{$entry->id}/img.jpg");
});

// ─────────────────────────────────────────────
// 21. Privater Bildabruf
// ─────────────────────────────────────────────

test('owner can retrieve image with correct headers', function () {
    Storage::fake('local');
    [$owner, $garden, $planting] = makePlantingWithOwner();
    $entry = PlantingEntry::factory()->for($planting, 'planting')->create();
    Storage::disk('local')->put("planting-entries/{$entry->id}/img.jpg", 'fake-jpeg-content');
    $image = PlantingEntryImage::factory()->for($entry, 'entry')->create([
        'path' => "planting-entries/{$entry->id}/img.jpg",
        'mime_type' => 'image/jpeg',
    ]);

    $response = $this->actingAs($owner)
        ->get(route('planting-entry-images.show', [$garden, $planting, $entry, $image]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'image/jpeg');
    expect($response->headers->get('Cache-Control'))->toContain('private');
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
});

test('stranger cannot retrieve image', function () {
    Storage::fake('local');
    [$owner, $garden, $planting] = makePlantingWithOwner();
    $entry = PlantingEntry::factory()->for($planting, 'planting')->create();
    Storage::disk('local')->put("planting-entries/{$entry->id}/img.jpg", 'x');
    $image = PlantingEntryImage::factory()->for($entry, 'entry')->create([
        'path' => "planting-entries/{$entry->id}/img.jpg",
    ]);
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->get(route('planting-entry-images.show', [$garden, $planting, $entry, $image]))
        ->assertForbidden();
});

test('missing file returns 404', function () {
    Storage::fake('local');
    [$owner, $garden, $planting] = makePlantingWithOwner();
    $entry = PlantingEntry::factory()->for($planting, 'planting')->create();
    $image = PlantingEntryImage::factory()->for($entry, 'entry')->create([
        'path' => 'planting-entries/1/nonexistent.jpg',
    ]);

    $this->actingAs($owner)
        ->get(route('planting-entry-images.show', [$garden, $planting, $entry, $image]))
        ->assertNotFound();
});

test('disallowed mime type in db returns 404', function () {
    Storage::fake('local');
    [$owner, $garden, $planting] = makePlantingWithOwner();
    $entry = PlantingEntry::factory()->for($planting, 'planting')->create();
    Storage::disk('local')->put("planting-entries/{$entry->id}/img.gif", 'x');
    $image = PlantingEntryImage::factory()->for($entry, 'entry')->create([
        'path' => "planting-entries/{$entry->id}/img.gif",
        'mime_type' => 'image/gif',
    ]);

    $this->actingAs($owner)
        ->get(route('planting-entry-images.show', [$garden, $planting, $entry, $image]))
        ->assertNotFound();
});

// ─────────────────────────────────────────────
// 22. MIME-Mapping
// ─────────────────────────────────────────────

test('jpeg upload results in .jpg extension on path', function () {
    Storage::fake('local');
    [$owner, $garden, $planting] = makePlantingWithOwner();

    $this->actingAs($owner)
        ->post(route('planting-entries.store', [$garden, $planting]), array_merge(
            validEntryData(),
            ['images' => [UploadedFile::fake()->image('photo.jpeg')]]
        ))
        ->assertRedirect();

    $image = $planting->entries()->first()->images()->first();
    expect($image->path)->toEndWith('.jpg');
});

test('png upload results in .png extension', function () {
    Storage::fake('local');
    [$owner, $garden, $planting] = makePlantingWithOwner();

    $this->actingAs($owner)
        ->post(route('planting-entries.store', [$garden, $planting]), array_merge(
            validEntryData(),
            ['images' => [UploadedFile::fake()->image('photo.png')]]
        ))
        ->assertRedirect();

    $image = $planting->entries()->first()->images()->first();
    expect($image->path)->toEndWith('.png');
});

test('webp upload results in .webp extension', function () {
    Storage::fake('local');
    [$owner, $garden, $planting] = makePlantingWithOwner();

    $this->actingAs($owner)
        ->post(route('planting-entries.store', [$garden, $planting]), array_merge(
            validEntryData(),
            ['images' => [UploadedFile::fake()->create('photo.webp', 100, 'image/webp')]]
        ))
        ->assertRedirect();

    $image = $planting->entries()->first()->images()->first();
    expect($image->path)->toEndWith('.webp');
});

test('PlantingEntry actions use ImageStorage for path generation', function () {
    Storage::fake('local');
    [$owner, $garden, $planting] = makePlantingWithOwner();

    $this->actingAs($owner)
        ->post(route('planting-entries.store', [$garden, $planting]), array_merge(
            validEntryData(),
            ['images' => [UploadedFile::fake()->image('photo.jpg')]]
        ))
        ->assertRedirect();

    $image = $planting->entries()->first()->images()->first();
    // UUID-based filename generated by ImageStorage::store()
    $filename = basename($image->path, '.jpg');
    expect($filename)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
    // Path uses the entry directory convention
    expect($image->path)->toStartWith('planting-entries/');
});

// ─────────────────────────────────────────────
// 23. Timeline-Props
// ─────────────────────────────────────────────

test('planting show contains entries prop without path', function () {
    [$owner, $garden, $planting] = makePlantingWithOwner();
    $entry = PlantingEntry::factory()->for($planting, 'planting')->create();

    $this->actingAs($owner)
        ->get(route('plantings.show', [$garden, $planting]))
        ->assertInertia(fn ($page) => $page
            ->component('plantings/show')
            ->has('entries.data', 1)
            ->where('entries.data.0.id', $entry->id)
            ->missing('entries.data.0.path')
        );
});

test('entries are sorted by occurred_at desc then id desc', function () {
    [$owner, $garden, $planting] = makePlantingWithOwner();
    $older = PlantingEntry::factory()->for($planting, 'planting')->create(['occurred_at' => '2026-01-01']);
    $newer = PlantingEntry::factory()->for($planting, 'planting')->create(['occurred_at' => '2026-06-15']);

    $this->actingAs($owner)
        ->get(route('plantings.show', [$garden, $planting]))
        ->assertInertia(fn ($page) => $page
            ->where('entries.data.0.id', $newer->id)
            ->where('entries.data.1.id', $older->id)
        );
});

test('entriesPage pagination parameter works', function () {
    [$owner, $garden, $planting] = makePlantingWithOwner();
    PlantingEntry::factory(12)->for($planting, 'planting')->create();

    $this->actingAs($owner)
        ->get(route('plantings.show', [$garden, $planting]).'?entriesPage=2')
        ->assertInertia(fn ($page) => $page
            ->where('entries.current_page', 2)
            ->has('entries.data', 2)
        );
});
