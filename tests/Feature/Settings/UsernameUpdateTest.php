<?php

use App\Enums\ProfileVisibility;
use App\Models\User;
use Illuminate\Testing\TestResponse;

function patchWithUsername(User $user, string $username): TestResponse
{
    return test()->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'username' => $username,
        'email' => $user->email,
        'profile_visibility' => ProfileVisibility::Private->value,
    ]);
}

test('username can be changed after registration (first change always allowed)', function () {
    $user = User::factory()->create(['username' => 'original', 'username_changed_at' => null]);

    patchWithUsername($user, 'newhandle')
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->username)->toBe('newhandle');
    expect($user->username_changed_at)->not->toBeNull();
});

test('unchanged username does not trigger cooldown or update timestamp', function () {
    $user = User::factory()->create([
        'username' => 'unchanged',
        'username_changed_at' => now()->subDays(1),
    ]);

    $originalTimestamp = $user->username_changed_at;

    patchWithUsername($user, 'unchanged')->assertSessionHasNoErrors();

    expect($user->refresh()->username_changed_at->eq($originalTimestamp))->toBeTrue();
});

test('case change only is not counted as change after normalization', function () {
    $user = User::factory()->create([
        'username' => 'myuser',
        'username_changed_at' => now()->subDays(1),
    ]);

    $originalTimestamp = $user->username_changed_at;

    patchWithUsername($user, 'MYUSER')->assertSessionHasNoErrors();

    expect($user->refresh()->username_changed_at->eq($originalTimestamp))->toBeTrue();
    expect($user->username)->toBe('myuser');
});

test('second username change within 30 days is rejected', function () {
    $user = User::factory()->create([
        'username' => 'firsthandle',
        'username_changed_at' => now()->subDays(5),
    ]);

    patchWithUsername($user, 'secondhandle')->assertSessionHasErrors('username');

    expect($user->refresh()->username)->toBe('firsthandle');
});

test('username change is allowed exactly when 30 day cooldown boundary is reached', function () {
    $user = User::factory()->create([
        'username' => 'oldhandle',
        'username_changed_at' => now()->subDays(30),
    ]);

    patchWithUsername($user, 'newhandle')->assertSessionHasNoErrors();

    expect($user->refresh()->username)->toBe('newhandle');
});

test('failed validation does not update username changed at', function () {
    $user = User::factory()->create([
        'username' => 'validuser',
        'username_changed_at' => null,
    ]);

    test()->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'username' => '_invalid_start',
        'email' => $user->email,
        'profile_visibility' => ProfileVisibility::Private->value,
    ])->assertSessionHasErrors('username');

    expect($user->refresh()->username_changed_at)->toBeNull();
    expect($user->username)->toBe('validuser');
});

test('reserved username cannot be set in profile', function () {
    $user = User::factory()->create(['username' => 'myuser', 'username_changed_at' => null]);

    patchWithUsername($user, 'admin')->assertSessionHasErrors('username');
    patchWithUsername($user, 'settings')->assertSessionHasErrors('username');
});

test('username must remain unique when changed', function () {
    $other = User::factory()->create(['username' => 'takenname']);
    $user = User::factory()->create(['username' => 'myname', 'username_changed_at' => null]);

    patchWithUsername($user, 'takenname')->assertSessionHasErrors('username');
});
