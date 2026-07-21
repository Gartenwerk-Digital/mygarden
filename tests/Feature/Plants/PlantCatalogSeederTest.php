<?php

use App\Enums\CatalogOrigin;
use App\Enums\UserRole;
use App\Models\PlantSpecies;
use App\Models\PlantVariety;
use App\Models\User;
use Database\Seeders\PlantCatalogSeeder;

beforeEach(function () {
    $this->seed(PlantCatalogSeeder::class);
});

test('seeder runs without errors', function () {
    expect(PlantSpecies::withTrashed()->count())->toBeGreaterThan(0);
});

test('seeder is idempotent – second run creates no duplicates', function () {
    $countBefore = PlantSpecies::withTrashed()->count();
    $varietyCountBefore = PlantVariety::withTrashed()->count();

    $this->seed(PlantCatalogSeeder::class);

    expect(PlantSpecies::withTrashed()->count())->toBe($countBefore);
    expect(PlantVariety::withTrashed()->count())->toBe($varietyCountBefore);
});

test('seeder creates system species', function () {
    expect(
        PlantSpecies::where('origin', CatalogOrigin::System->value)->count()
    )->toBeGreaterThan(0);
});

test('seeder creates community species with created_by', function () {
    $community = PlantSpecies::where('origin', CatalogOrigin::User->value)->first();

    expect($community)->not->toBeNull();
    expect($community->created_by)->not->toBeNull();
});

test('seeder creates at least one species without botanical_name', function () {
    expect(
        PlantSpecies::whereNull('botanical_name')->count()
    )->toBeGreaterThan(0);
});

test('seeder creates species with multiple standort values', function () {
    $withMultipleSun = PlantSpecies::withTrashed()
        ->whereNotNull('sun_exposures')
        ->get()
        ->filter(fn ($s) => is_array($s->sun_exposures) && count($s->sun_exposures) > 1);

    expect($withMultipleSun->count())->toBeGreaterThan(0);
});

test('seeder creates at least one active variety', function () {
    expect(
        PlantVariety::whereNull('archived_at')->count()
    )->toBeGreaterThan(0);
});

test('seeder creates at least one archived variety', function () {
    expect(
        PlantVariety::whereNotNull('archived_at')->count()
    )->toBeGreaterThan(0);
});

test('seeder creates enough species for pagination to be relevant', function () {
    // 24 Einträge pro Seite – mindestens 25 aktive Species um zweite Seite zu ermöglichen
    expect(
        PlantSpecies::whereNull('archived_at')->count()
    )->toBeGreaterThan(24);
});

test('archived species has no active varieties', function () {
    $archived = PlantSpecies::whereNotNull('archived_at')->first();

    expect($archived)->not->toBeNull();
    expect($archived->varieties()->whereNull('archived_at')->count())->toBe(0);
});

test('seeder creates species in multiple categories', function () {
    $categories = PlantSpecies::withTrashed()
        ->distinct()
        ->pluck('category')
        ->toArray();

    expect(count($categories))->toBeGreaterThan(3);
});

test('local dev user chrispuh receives admin role from seeder', function () {
    $this->app['env'] = 'local';

    $this->seed(PlantCatalogSeeder::class);

    $user = User::where('email', 'chrisganzert@gmail.com')->first();

    expect($user)->not->toBeNull();
    expect($user->role)->toBe(UserRole::Admin);
    expect($user->canModerateCatalog())->toBeTrue();
});

test('local dev user admin can access moderation routes', function () {
    $this->app['env'] = 'local';
    $this->seed(PlantCatalogSeeder::class);

    $admin = User::where('email', 'chrisganzert@gmail.com')->first();

    $this->actingAs($admin)
        ->get(route('moderation.plants.index'))
        ->assertOk();
});

test('seeder is idempotent for local dev user – second run does not create duplicate', function () {
    $this->app['env'] = 'local';

    $this->seed(PlantCatalogSeeder::class);
    $countAfterFirst = User::where('email', 'chrisganzert@gmail.com')->count();

    $this->seed(PlantCatalogSeeder::class);
    $countAfterSecond = User::where('email', 'chrisganzert@gmail.com')->count();

    expect($countAfterFirst)->toBe(1);
    expect($countAfterSecond)->toBe(1);
});
