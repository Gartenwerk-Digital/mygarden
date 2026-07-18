<?php

use App\Enums\ProfileVisibility;
use App\Models\User;

function validProfileData(User $user, array $overrides = []): array
{
    return array_merge([
        'name' => $user->name,
        'username' => $user->username,
        'email' => $user->email,
        'bio' => null,
        'country' => null,
        'region' => null,
        'profile_visibility' => ProfileVisibility::Private->value,
    ], $overrides);
}

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), validProfileData($user, [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), validProfileData($user))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('profile can be updated with new fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), validProfileData($user, [
            'bio' => 'Ich liebe meinen Garten.',
            'country' => 'DE',
            'region' => 'Bayern',
            'profile_visibility' => ProfileVisibility::Public->value,
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->bio)->toBe('Ich liebe meinen Garten.');
    expect($user->country)->toBe('DE');
    expect($user->region)->toBe('Bayern');
    expect($user->profile_visibility)->toBe(ProfileVisibility::Public);
});

test('bio can be set and cleared', function () {
    $user = User::factory()->create(['bio' => 'Alte Bio']);

    $this->actingAs($user)
        ->patch(route('profile.update'), validProfileData($user, ['bio' => null]))
        ->assertSessionHasNoErrors();

    expect($user->refresh()->bio)->toBeNull();
});

test('country must be a valid iso code', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), validProfileData($user, ['country' => 'XX']))
        ->assertSessionHasErrors('country');
});

test('country input is normalized to uppercase', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), validProfileData($user, ['country' => 'de']))
        ->assertSessionHasNoErrors();

    expect($user->refresh()->country)->toBe('DE');
});

test('region can be set', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), validProfileData($user, ['region' => 'Oberbayern']))
        ->assertSessionHasNoErrors();

    expect($user->refresh()->region)->toBe('Oberbayern');
});

test('profile visibility can be changed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), validProfileData($user, [
            'profile_visibility' => ProfileVisibility::Public->value,
        ]))
        ->assertSessionHasNoErrors();

    expect($user->refresh()->profile_visibility)->toBe(ProfileVisibility::Public);
});

test('profile visibility defaults to private', function () {
    $user = User::factory()->create();

    expect($user->profile_visibility)->toBe(ProfileVisibility::Private);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertGuest();
    expect($user->fresh())->toBeNull();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), ['password' => 'wrong-password'])
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh())->not->toBeNull();
});
