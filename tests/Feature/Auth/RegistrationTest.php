<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;

function validRegistrationData(array $overrides = []): array
{
    return array_merge([
        'name' => 'Test User',
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'age_confirmation' => true,
        'terms_accepted' => true,
    ], $overrides);
}

test('registration screen can be rendered', function () {
    $this->get(route('register'))->assertOk();
});

test('registration screen includes team invitation context', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Laravel Team']);
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $this->get(route('register', ['invitation' => $invitation->code]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/register')
            ->where('teamInvitation.code', $invitation->code)
            ->where('teamInvitation.teamName', 'Laravel Team'),
        );
});

test('new users can register', function () {
    $this->post(route('register.store'), validRegistrationData());

    $this->assertAuthenticated();

    $user = User::where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->username)->toBe('testuser');
    expect($user->terms_accepted_at)->not->toBeNull();
});

test('registration requires a username', function () {
    $this->post(route('register.store'), validRegistrationData(['username' => '']))
        ->assertSessionHasErrors('username');
});

test('username must be unique', function () {
    User::factory()->create(['username' => 'existinguser']);

    $this->post(route('register.store'), validRegistrationData(['username' => 'existinguser']))
        ->assertSessionHasErrors('username');
});

test('username must be valid format', function () {
    foreach (['chris-g', 'chris.g', 'chris g', 'ch!r!s'] as $invalid) {
        $this->post(route('register.store'), validRegistrationData(['username' => $invalid]))
            ->assertSessionHasErrors('username');
    }
});

test('username input is normalized to lowercase before validation', function () {
    $this->post(route('register.store'), validRegistrationData(['username' => 'TestUser42']));

    $this->assertAuthenticated();
    expect(User::where('email', 'test@example.com')->value('username'))->toBe('testuser42');
});

test('username cannot start with underscore', function () {
    $this->post(route('register.store'), validRegistrationData(['username' => '_testuser']))
        ->assertSessionHasErrors('username');
});

test('username cannot end with underscore', function () {
    $this->post(route('register.store'), validRegistrationData(['username' => 'testuser_']))
        ->assertSessionHasErrors('username');
});

test('username cannot contain consecutive underscores', function () {
    $this->post(route('register.store'), validRegistrationData(['username' => 'test__user']))
        ->assertSessionHasErrors('username');
});

test('reserved usernames are rejected', function () {
    foreach (['admin', 'settings', 'me', 'login'] as $reserved) {
        $this->post(route('register.store'), validRegistrationData(['username' => $reserved]))
            ->assertSessionHasErrors('username');
    }
});

test('registration requires age confirmation', function () {
    $this->post(route('register.store'), validRegistrationData(['age_confirmation' => false]))
        ->assertSessionHasErrors('age_confirmation');
});

test('age confirmation is not stored in database', function () {
    $this->post(route('register.store'), validRegistrationData());

    $user = User::where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull();

    $columns = Schema::getColumnListing('users');
    expect($columns)->not->toContain('age_confirmation');
});

test('registration requires terms acceptance', function () {
    $this->post(route('register.store'), validRegistrationData(['terms_accepted' => false]))
        ->assertSessionHasErrors('terms_accepted');
});

test('terms accepted at is set on registration', function () {
    $this->post(route('register.store'), validRegistrationData());

    $user = User::where('email', 'test@example.com')->first();
    expect($user->terms_accepted_at)->not->toBeNull();
});

test('registration does not set username changed at', function () {
    $this->post(route('register.store'), validRegistrationData());

    $user = User::where('email', 'test@example.com')->first();
    expect($user->username_changed_at)->toBeNull();
});
