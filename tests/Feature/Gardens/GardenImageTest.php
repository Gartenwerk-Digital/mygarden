<?php

use App\Actions\Gardens\DeleteGardenImage;
use App\Actions\Gardens\ForceDeleteGarden;
use App\Actions\Gardens\SetGardenCoverImage;
use App\Actions\Gardens\UnsetGardenCoverImage;
use App\Actions\Gardens\UploadGardenImages;
use App\Models\Garden;
use App\Models\GardenImage;
use App\Models\User;
use App\Support\ImageStorage;
use App\Support\StoredImage;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

// ─────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────

function makeGardenWithOwner(): array
{
    $owner = User::factory()->create();
    $garden = Garden::factory()->create(['user_id' => $owner->id]);

    return [$owner, $garden];
}

function gardenJpeg(string $name = 'photo.jpg', int $kb = 100): UploadedFile
{
    return UploadedFile::fake()->image($name, 100, 100)->size($kb);
}

// ─────────────────────────────────────────────
// 1. Auth
// ─────────────────────────────────────────────

test('gast wird beim upload auf login geleitet', function () {
    [$owner, $garden] = makeGardenWithOwner();

    $this->post(route('gardens.images.store', $garden), ['images' => [gardenJpeg()]])
        ->assertRedirect(route('login'));
});

test('gast wird bei bildabruf auf login geleitet', function () {
    [$owner, $garden] = makeGardenWithOwner();
    $image = GardenImage::factory()->for($garden)->create();

    $this->get(route('gardens.images.show', [$garden, $image]))
        ->assertRedirect(route('login'));
});

test('gast wird beim löschen auf login geleitet', function () {
    [$owner, $garden] = makeGardenWithOwner();
    $image = GardenImage::factory()->for($garden)->create();

    $this->delete(route('gardens.images.destroy', [$garden, $image]))
        ->assertRedirect(route('login'));
});

test('gast wird beim cover setzen auf login geleitet', function () {
    [$owner, $garden] = makeGardenWithOwner();
    $image = GardenImage::factory()->for($garden)->create();

    $this->put(route('gardens.images.cover.set', [$garden, $image]))
        ->assertRedirect(route('login'));
});

// ─────────────────────────────────────────────
// 2. Owner-Autorisierung + Scoped Route Binding
// ─────────────────────────────────────────────

test('fremdnutzer erhält 403 beim upload', function () {
    [$owner, $garden] = makeGardenWithOwner();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->post(route('gardens.images.store', $garden), ['images' => [gardenJpeg()]])
        ->assertForbidden();
});

test('fremdnutzer kann bild nicht abrufen', function () {
    Storage::fake('local');
    [$owner, $garden] = makeGardenWithOwner();
    Storage::disk('local')->put("gardens/{$garden->id}/x.jpg", 'x');
    $image = GardenImage::factory()->for($garden)->create([
        'path' => "gardens/{$garden->id}/x.jpg",
    ]);
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->get(route('gardens.images.show', [$garden, $image]))
        ->assertForbidden();
});

test('bild aus fremdem garden wird per scope binding zu 404', function () {
    [$owner, $garden] = makeGardenWithOwner();
    [$other, $otherGarden] = makeGardenWithOwner();
    $foreignImage = GardenImage::factory()->for($otherGarden)->create();

    $this->actingAs($owner)
        ->get(route('gardens.images.show', [$garden, $foreignImage]))
        ->assertNotFound();
});

test('fremdes bild kann nicht als cover eines gardens gesetzt werden', function () {
    [$owner, $garden] = makeGardenWithOwner();
    [$other, $otherGarden] = makeGardenWithOwner();
    $foreignImage = GardenImage::factory()->for($otherGarden)->create();

    $this->actingAs($owner)
        ->put(route('gardens.images.cover.set', [$garden, $foreignImage]))
        ->assertNotFound();
});

// ─────────────────────────────────────────────
// 3. Upload
// ─────────────────────────────────────────────

test('owner kann ein bild hochladen', function () {
    Storage::fake('local');
    [$owner, $garden] = makeGardenWithOwner();

    $this->actingAs($owner)
        ->post(route('gardens.images.store', $garden), [
            'images' => [gardenJpeg()],
        ])
        ->assertRedirect();

    $image = $garden->images()->first();
    expect($image)->not->toBeNull()
        ->and($image->path)->toStartWith("gardens/{$garden->id}/")
        ->and($image->mime_type)->toBe('image/jpeg')
        ->and($image->size)->toBeGreaterThan(0)
        ->and($image->sort_order)->toBe(0)
        ->and($image->is_cover)->toBeNull();

    Storage::disk('local')->assertExists($image->path);
});

test('owner kann mehrere bilder in einem request hochladen', function () {
    Storage::fake('local');
    [$owner, $garden] = makeGardenWithOwner();

    $this->actingAs($owner)
        ->post(route('gardens.images.store', $garden), [
            'images' => [gardenJpeg('a.jpg'), gardenJpeg('b.jpg'), gardenJpeg('c.jpg')],
        ])
        ->assertRedirect();

    expect($garden->images()->count())->toBe(3);

    $sortOrders = $garden->images()->pluck('sort_order')->all();
    expect($sortOrders)->toBe([0, 1, 2]);
});

test('mehr als 5 bilder pro request werden abgelehnt', function () {
    Storage::fake('local');
    [$owner, $garden] = makeGardenWithOwner();

    $this->actingAs($owner)
        ->post(route('gardens.images.store', $garden), [
            'images' => [
                gardenJpeg('1.jpg'), gardenJpeg('2.jpg'), gardenJpeg('3.jpg'),
                gardenJpeg('4.jpg'), gardenJpeg('5.jpg'), gardenJpeg('6.jpg'),
            ],
        ])
        ->assertSessionHasErrors('images');

    expect($garden->images()->count())->toBe(0);
});

test('gesamtlimit 20 wird durchgesetzt', function () {
    Storage::fake('local');
    [$owner, $garden] = makeGardenWithOwner();
    GardenImage::factory()->for($garden)->count(18)->create();

    $this->actingAs($owner)
        ->post(route('gardens.images.store', $garden), [
            'images' => [gardenJpeg('a.jpg'), gardenJpeg('b.jpg'), gardenJpeg('c.jpg')],
        ])
        ->assertSessionHasErrors('images');

    expect($garden->images()->count())->toBe(18);
});

test('nicht erlaubter mime-typ wird abgelehnt', function () {
    Storage::fake('local');
    [$owner, $garden] = makeGardenWithOwner();

    $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

    $this->actingAs($owner)
        ->post(route('gardens.images.store', $garden), ['images' => [$file]])
        ->assertSessionHasErrors('images.0');

    expect($garden->images()->count())->toBe(0);
});

test('manipulierte dateiendung wird abgelehnt und nichts gespeichert', function () {
    Storage::fake('local');
    [$owner, $garden] = makeGardenWithOwner();

    $file = UploadedFile::fake()->create('fake.jpg', 100, 'application/pdf');

    $this->actingAs($owner)
        ->post(route('gardens.images.store', $garden), ['images' => [$file]])
        ->assertSessionHasErrors('images.0');

    expect($garden->images()->count())->toBe(0);
    expect(Storage::disk('local')->allFiles("gardens/{$garden->id}"))->toBe([]);
});

test('bild über 3 MB wird abgelehnt', function () {
    Storage::fake('local');
    [$owner, $garden] = makeGardenWithOwner();

    $file = UploadedFile::fake()->image('big.jpg')->size(4 * 1024);

    $this->actingAs($owner)
        ->post(route('gardens.images.store', $garden), ['images' => [$file]])
        ->assertSessionHasErrors('images.0');

    expect($garden->images()->count())->toBe(0);
});

// ─────────────────────────────────────────────
// 4. Konfigurierbare Disk
// ─────────────────────────────────────────────

test('upload verwendet konfigurierte disk', function () {
    config(['mygarden.images.disk' => 'testing']);
    Storage::fake('testing');
    Storage::fake('local');
    [$owner, $garden] = makeGardenWithOwner();

    $this->actingAs($owner)
        ->post(route('gardens.images.store', $garden), ['images' => [gardenJpeg()]])
        ->assertRedirect();

    $image = $garden->images()->first();
    Storage::disk('testing')->assertExists($image->path);
    Storage::disk('local')->assertMissing($image->path);
});

// ─────────────────────────────────────────────
// 5. MySQL Unique-Constraint (is_cover)
// ─────────────────────────────────────────────

test('mehrere garden_images mit is_cover null sind erlaubt', function () {
    [$owner, $garden] = makeGardenWithOwner();

    GardenImage::factory()->for($garden)->count(3)->create(['is_cover' => null]);

    expect($garden->images()->count())->toBe(3);
});

test('zwei is_cover true zeilen für denselben garden werfen QueryException', function () {
    [$owner, $garden] = makeGardenWithOwner();
    GardenImage::factory()->for($garden)->cover()->create();

    expect(fn () => GardenImage::factory()->for($garden)->cover()->create())
        ->toThrow(QueryException::class);
});

test('is_cover true ist pro garden erlaubt (unterschiedliche gardens)', function () {
    [$owner1, $garden1] = makeGardenWithOwner();
    [$owner2, $garden2] = makeGardenWithOwner();

    GardenImage::factory()->for($garden1)->cover()->create();
    GardenImage::factory()->for($garden2)->cover()->create();

    expect(true)->toBeTrue();
});

// ─────────────────────────────────────────────
// 6. Cover setzen / wechseln / entfernen
// ─────────────────────────────────────────────

test('cover kann gesetzt werden', function () {
    [$owner, $garden] = makeGardenWithOwner();
    $image = GardenImage::factory()->for($garden)->create();

    $this->actingAs($owner)
        ->put(route('gardens.images.cover.set', [$garden, $image]))
        ->assertRedirect();

    expect($image->fresh()->is_cover)->toBeTrue();
});

test('cover wechseln setzt altes auf null', function () {
    [$owner, $garden] = makeGardenWithOwner();
    $oldCover = GardenImage::factory()->for($garden)->cover()->create();
    $newCover = GardenImage::factory()->for($garden)->create();

    $this->actingAs($owner)
        ->put(route('gardens.images.cover.set', [$garden, $newCover]))
        ->assertRedirect();

    expect($oldCover->fresh()->is_cover)->toBeNull()
        ->and($newCover->fresh()->is_cover)->toBeTrue();
});

test('cover kann entfernt werden', function () {
    [$owner, $garden] = makeGardenWithOwner();
    $cover = GardenImage::factory()->for($garden)->cover()->create();

    $this->actingAs($owner)
        ->delete(route('gardens.images.cover.unset', $garden))
        ->assertRedirect();

    expect($cover->fresh()->is_cover)->toBeNull();
});

// ─────────────────────────────────────────────
// 7. Löschen
// ─────────────────────────────────────────────

test('nicht-cover-bild löschen entfernt datei nach commit', function () {
    Storage::fake('local');
    [$owner, $garden] = makeGardenWithOwner();
    Storage::disk('local')->put("gardens/{$garden->id}/x.jpg", 'x');
    $image = GardenImage::factory()->for($garden)->create([
        'path' => "gardens/{$garden->id}/x.jpg",
    ]);
    $cover = GardenImage::factory()->for($garden)->cover()->create();

    $this->actingAs($owner)
        ->delete(route('gardens.images.destroy', [$garden, $image]))
        ->assertRedirect();

    expect(GardenImage::find($image->id))->toBeNull()
        ->and($cover->fresh()->is_cover)->toBeTrue();
    Storage::disk('local')->assertMissing("gardens/{$garden->id}/x.jpg");
});

test('cover-bild löschen lässt garden ohne cover und rückt nichts nach', function () {
    Storage::fake('local');
    [$owner, $garden] = makeGardenWithOwner();
    Storage::disk('local')->put("gardens/{$garden->id}/c.jpg", 'c');
    $cover = GardenImage::factory()->for($garden)->cover()->create([
        'path' => "gardens/{$garden->id}/c.jpg",
    ]);
    $other = GardenImage::factory()->for($garden)->create();

    $this->actingAs($owner)
        ->delete(route('gardens.images.destroy', [$garden, $cover]))
        ->assertRedirect();

    expect(GardenImage::find($cover->id))->toBeNull()
        ->and($other->fresh()->is_cover)->toBeNull()
        ->and($garden->fresh()->coverImage()->exists())->toBeFalse();
});

// ─────────────────────────────────────────────
// 8. Private Auslieferung
// ─────────────────────────────────────────────

test('owner kann bild mit korrekten headern abrufen', function () {
    Storage::fake('local');
    [$owner, $garden] = makeGardenWithOwner();
    Storage::disk('local')->put("gardens/{$garden->id}/x.jpg", 'jpg-bytes');
    $image = GardenImage::factory()->for($garden)->create([
        'path' => "gardens/{$garden->id}/x.jpg",
        'mime_type' => 'image/jpeg',
    ]);

    $response = $this->actingAs($owner)
        ->get(route('gardens.images.show', [$garden, $image]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'image/jpeg');
    expect($response->headers->get('Cache-Control'))->toContain('private');
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
});

test('fehlende datei ergibt 404', function () {
    Storage::fake('local');
    [$owner, $garden] = makeGardenWithOwner();
    $image = GardenImage::factory()->for($garden)->create([
        'path' => "gardens/{$garden->id}/missing.jpg",
    ]);

    $this->actingAs($owner)
        ->get(route('gardens.images.show', [$garden, $image]))
        ->assertNotFound();
});

test('nicht erlaubter mime in db ergibt 404', function () {
    Storage::fake('local');
    [$owner, $garden] = makeGardenWithOwner();
    Storage::disk('local')->put("gardens/{$garden->id}/x.gif", 'x');
    $image = GardenImage::factory()->for($garden)->create([
        'path' => "gardens/{$garden->id}/x.gif",
        'mime_type' => 'image/gif',
    ]);

    $this->actingAs($owner)
        ->get(route('gardens.images.show', [$garden, $image]))
        ->assertNotFound();
});

// ─────────────────────────────────────────────
// 9. Soft Delete / Restore
// ─────────────────────────────────────────────

test('soft-delete des gardens lässt bilder und dateien bestehen', function () {
    Storage::fake('local');
    [$owner, $garden] = makeGardenWithOwner();
    Storage::disk('local')->put("gardens/{$garden->id}/x.jpg", 'x');
    $image = GardenImage::factory()->for($garden)->create([
        'path' => "gardens/{$garden->id}/x.jpg",
    ]);

    $garden->delete();

    expect(GardenImage::find($image->id))->not->toBeNull();
    Storage::disk('local')->assertExists("gardens/{$garden->id}/x.jpg");
});

test('soft-delete blockiert bildroute', function () {
    Storage::fake('local');
    [$owner, $garden] = makeGardenWithOwner();
    Storage::disk('local')->put("gardens/{$garden->id}/x.jpg", 'x');
    $image = GardenImage::factory()->for($garden)->create([
        'path' => "gardens/{$garden->id}/x.jpg",
    ]);

    $garden->delete();

    $this->actingAs($owner)
        ->get(route('gardens.images.show', [$garden->id, $image->id]))
        ->assertNotFound();
});

test('restore stellt bildzugriff wieder her', function () {
    Storage::fake('local');
    [$owner, $garden] = makeGardenWithOwner();
    Storage::disk('local')->put("gardens/{$garden->id}/x.jpg", 'x');
    $image = GardenImage::factory()->for($garden)->create([
        'path' => "gardens/{$garden->id}/x.jpg",
        'mime_type' => 'image/jpeg',
    ]);

    $garden->delete();
    $garden->restore();

    $this->actingAs($owner)
        ->get(route('gardens.images.show', [$garden, $image]))
        ->assertOk();
});

// ─────────────────────────────────────────────
// 10. Force Delete Garden entfernt garden_images + dateien
// ─────────────────────────────────────────────

test('ForceDeleteGarden entfernt garden_images und deren dateien', function () {
    Storage::fake('local');
    [$owner, $garden] = makeGardenWithOwner();
    Storage::disk('local')->put("gardens/{$garden->id}/a.jpg", 'a');
    Storage::disk('local')->put("gardens/{$garden->id}/b.jpg", 'b');
    GardenImage::factory()->for($garden)->create(['path' => "gardens/{$garden->id}/a.jpg"]);
    GardenImage::factory()->for($garden)->cover()->create(['path' => "gardens/{$garden->id}/b.jpg"]);

    (new ForceDeleteGarden(new ImageStorage))->handle($garden, $owner);

    expect(Garden::withTrashed()->find($garden->id))->toBeNull()
        ->and(GardenImage::count())->toBe(0);
    Storage::disk('local')->assertMissing("gardens/{$garden->id}/a.jpg");
    Storage::disk('local')->assertMissing("gardens/{$garden->id}/b.jpg");
});

// ─────────────────────────────────────────────
// 11. Direkte Action-Autorisierung
// ─────────────────────────────────────────────

test('UploadGardenImages durch fremdnutzer wirft AuthorizationException', function () {
    Storage::fake('local');
    [$owner, $garden] = makeGardenWithOwner();
    $stranger = User::factory()->create();

    expect(fn () => (new UploadGardenImages(new ImageStorage))->handle($stranger, $garden, [gardenJpeg()]))
        ->toThrow(AuthorizationException::class);
});

test('DeleteGardenImage durch fremdnutzer wirft AuthorizationException', function () {
    [$owner, $garden] = makeGardenWithOwner();
    $image = GardenImage::factory()->for($garden)->create();
    $stranger = User::factory()->create();

    expect(fn () => (new DeleteGardenImage(new ImageStorage))->handle($stranger, $garden, $image))
        ->toThrow(AuthorizationException::class);
});

test('SetGardenCoverImage mit fremdem bild wirft AuthorizationException', function () {
    [$owner, $garden] = makeGardenWithOwner();
    [$other, $otherGarden] = makeGardenWithOwner();
    $foreign = GardenImage::factory()->for($otherGarden)->create();

    expect(fn () => (new SetGardenCoverImage)->handle($owner, $garden, $foreign))
        ->toThrow(AuthorizationException::class);
});

test('actions verweigern soft-gelöschten garden', function () {
    Storage::fake('local');
    [$owner, $garden] = makeGardenWithOwner();
    $garden->delete();

    expect(fn () => (new UploadGardenImages(new ImageStorage))->handle($owner, $garden, [gardenJpeg()]))
        ->toThrow(AuthorizationException::class);

    expect(fn () => (new UnsetGardenCoverImage)->handle($owner, $garden))
        ->toThrow(AuthorizationException::class);
});

// ─────────────────────────────────────────────
// 12. Rollback + Cleanup
// ─────────────────────────────────────────────

test('upload rollback entfernt bereits geschriebene dateien und legt keine zeilen an', function () {
    Storage::fake('local');
    [$owner, $garden] = makeGardenWithOwner();

    $failingStorage = new class extends ImageStorage
    {
        public int $calls = 0;

        public function store(UploadedFile $file, string $directory): StoredImage
        {
            $this->calls++;
            if ($this->calls === 2) {
                throw new RuntimeException('boom');
            }

            return parent::store($file, $directory);
        }
    };

    $action = new UploadGardenImages($failingStorage);

    expect(fn () => $action->handle($owner, $garden, [gardenJpeg('a.jpg'), gardenJpeg('b.jpg')]))
        ->toThrow(RuntimeException::class);

    expect($garden->images()->count())->toBe(0);
    expect(Storage::disk('local')->allFiles("gardens/{$garden->id}"))->toBe([]);
});

// ─────────────────────────────────────────────
// 13. Concurrency-Simulation Upload
// ─────────────────────────────────────────────

test('sequenzielle uploads überschreiten das 20er-limit nicht', function () {
    Storage::fake('local');
    [$owner, $garden] = makeGardenWithOwner();
    GardenImage::factory()->for($garden)->count(18)->create();

    $action = new UploadGardenImages(new ImageStorage);
    $action->handle($owner, $garden, [gardenJpeg('a.jpg'), gardenJpeg('b.jpg')]);

    expect(fn () => $action->handle($owner, $garden, [gardenJpeg('c.jpg')]))
        ->toThrow(ValidationException::class);

    expect($garden->fresh()->images()->count())->toBe(20);
});

test('sort_order bleibt für bestehende bilder stabil und nachfolgende uploads knüpfen an aktuelles maximum an', function () {
    Storage::fake('local');
    [$owner, $garden] = makeGardenWithOwner();

    $action = new UploadGardenImages(new ImageStorage);
    $action->handle($owner, $garden, [gardenJpeg('a.jpg'), gardenJpeg('b.jpg'), gardenJpeg('c.jpg')]);
    // Bild in der Mitte (sort=1) löschen; verbleibende Werte 0 und 2 bleiben unverändert.
    $middle = GardenImage::query()->where('garden_id', $garden->id)->where('sort_order', 1)->first();
    (new DeleteGardenImage(new ImageStorage))->handle($owner, $garden, $middle);

    $action->handle($owner, $garden, [gardenJpeg('d.jpg')]);
    $sortOrders = $garden->fresh()->images()->pluck('sort_order')->all();

    // Bestehende sort_orders sind nicht neu nummeriert; neuer Wert = aktuelles Maximum + 1.
    expect($sortOrders)->toBe([0, 2, 3]);
});

// ─────────────────────────────────────────────
// 14. Inertia-Props: kein path
// ─────────────────────────────────────────────

test('garden show enthält images ohne path', function () {
    [$owner, $garden] = makeGardenWithOwner();
    GardenImage::factory()->for($garden)->create();
    GardenImage::factory()->for($garden)->cover()->create();

    $this->actingAs($owner)
        ->get(route('gardens.show', $garden))
        ->assertInertia(fn ($page) => $page
            ->component('gardens/show')
            ->has('garden.images.0', fn ($img) => $img
                ->missing('path')
                ->has('id')
                ->has('mime_type')
                ->has('is_cover')
                ->etc()
            )
            ->has('garden.cover_image', fn ($img) => $img
                ->missing('path')
                ->where('is_cover', true)
                ->etc()
            )
        );
});

test('garden edit enthält images ohne path', function () {
    [$owner, $garden] = makeGardenWithOwner();
    GardenImage::factory()->for($garden)->create();

    $this->actingAs($owner)
        ->get(route('gardens.edit', $garden))
        ->assertInertia(fn ($page) => $page
            ->component('gardens/edit')
            ->has('garden.images.0', fn ($img) => $img
                ->missing('path')
                ->etc()
            )
        );
});
