<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('default role is user', function () {
    $user = User::factory()->create();

    expect($user->role)->toBe(UserRole::User);
});

test('isAdmin returns true only for admin role', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin->value]);
    $moderator = User::factory()->create(['role' => UserRole::Moderator->value]);
    $user = User::factory()->create(['role' => UserRole::User->value]);

    expect($admin->isAdmin())->toBeTrue();
    expect($moderator->isAdmin())->toBeFalse();
    expect($user->isAdmin())->toBeFalse();
});

test('isModerator returns true only for moderator role', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin->value]);
    $moderator = User::factory()->create(['role' => UserRole::Moderator->value]);
    $user = User::factory()->create(['role' => UserRole::User->value]);

    expect($admin->isModerator())->toBeFalse();
    expect($moderator->isModerator())->toBeTrue();
    expect($user->isModerator())->toBeFalse();
});

test('canModerateCatalog returns true for moderator and admin', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin->value]);
    $moderator = User::factory()->create(['role' => UserRole::Moderator->value]);
    $user = User::factory()->create(['role' => UserRole::User->value]);

    expect($admin->canModerateCatalog())->toBeTrue();
    expect($moderator->canModerateCatalog())->toBeTrue();
    expect($user->canModerateCatalog())->toBeFalse();
});

test('moderate-catalog gate allows moderator and admin', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin->value]);
    $moderator = User::factory()->create(['role' => UserRole::Moderator->value]);
    $user = User::factory()->create(['role' => UserRole::User->value]);

    expect(Gate::forUser($admin)->allows('moderate-catalog'))->toBeTrue();
    expect(Gate::forUser($moderator)->allows('moderate-catalog'))->toBeTrue();
    expect(Gate::forUser($user)->allows('moderate-catalog'))->toBeFalse();
});

test('manage-users gate allows only admin', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin->value]);
    $moderator = User::factory()->create(['role' => UserRole::Moderator->value]);
    $user = User::factory()->create(['role' => UserRole::User->value]);

    expect(Gate::forUser($admin)->allows('manage-users'))->toBeTrue();
    expect(Gate::forUser($moderator)->allows('manage-users'))->toBeFalse();
    expect(Gate::forUser($user)->allows('manage-users'))->toBeFalse();
});

test('role is cast to UserRole enum', function () {
    $user = User::factory()->create(['role' => 'moderator']);

    expect($user->fresh()->role)->toBe(UserRole::Moderator);
});
