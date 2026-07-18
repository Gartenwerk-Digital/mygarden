<?php

use App\Enums\ProfileVisibility;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

beforeEach(fn () => Storage::fake('public'));

function patchWithAvatar(User $user, array $overrides = []): TestResponse
{
    return test()->actingAs($user)->patch(route('profile.update'), array_merge([
        'name' => $user->name,
        'username' => $user->username,
        'email' => $user->email,
        'profile_visibility' => ProfileVisibility::Private->value,
    ], $overrides));
}

test('valid avatar upload is stored', function () {
    $user = User::factory()->create();

    patchWithAvatar($user, [
        'avatar' => UploadedFile::fake()->image('photo.jpg', 200, 200),
    ])->assertSessionHasNoErrors();

    $path = $user->refresh()->avatar_path;
    expect($path)->not->toBeNull();
    Storage::disk('public')->assertExists($path);
});

test('old avatar is deleted after successful replacement', function () {
    Storage::disk('public')->put('avatars/old.jpg', 'old content');
    $user = User::factory()->create(['avatar_path' => 'avatars/old.jpg']);

    patchWithAvatar($user, [
        'avatar' => UploadedFile::fake()->image('new.jpg', 200, 200),
    ])->assertSessionHasNoErrors();

    Storage::disk('public')->assertMissing('avatars/old.jpg');
    Storage::disk('public')->assertExists($user->refresh()->avatar_path);
});

test('avatar can be explicitly removed', function () {
    Storage::disk('public')->put('avatars/existing.jpg', 'content');
    $user = User::factory()->create(['avatar_path' => 'avatars/existing.jpg']);

    patchWithAvatar($user, ['remove_avatar' => true])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->avatar_path)->toBeNull();
    Storage::disk('public')->assertMissing('avatars/existing.jpg');
});

test('invalid file type is rejected', function () {
    $user = User::factory()->create();

    patchWithAvatar($user, [
        'avatar' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
    ])->assertSessionHasErrors('avatar');
});

test('gif file type is rejected', function () {
    $user = User::factory()->create();

    patchWithAvatar($user, [
        'avatar' => UploadedFile::fake()->image('animated.gif', 100, 100),
    ])->assertSessionHasErrors('avatar');
});

test('file too large is rejected', function () {
    $user = User::factory()->create();

    patchWithAvatar($user, [
        'avatar' => UploadedFile::fake()->image('large.jpg')->size(3000),
    ])->assertSessionHasErrors('avatar');
});

test('profile remains functional without avatar', function () {
    $user = User::factory()->create(['avatar_path' => null]);

    patchWithAvatar($user)->assertSessionHasNoErrors();

    expect($user->refresh()->avatar_path)->toBeNull();
});
