<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class UpdateUserProfile
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(User $user, array $validated, ?UploadedFile $avatarFile, bool $removeAvatar): void
    {
        $normalizedUsername = $validated['username'];

        if ($normalizedUsername !== $user->username && ! $user->canChangeUsername()) {
            throw ValidationException::withMessages([
                'username' => 'Du kannst deinen Benutzernamen erst ab dem '
                    .$user->nextUsernameChangeAllowedAt()->format('d.m.Y')
                    .' wieder ändern.',
            ]);
        }

        $oldAvatarPath = $user->avatar_path;
        $newAvatarPath = null;
        $avatarWasReplaced = false;

        if ($avatarFile) {
            $newAvatarPath = $avatarFile->store('avatars', 'public');
            $avatarWasReplaced = true;
        } elseif ($removeAvatar) {
            $avatarWasReplaced = true;
        }

        try {
            DB::transaction(function () use ($user, $validated, $normalizedUsername, $newAvatarPath, $removeAvatar) {
                $emailChanged = isset($validated['email']) && $validated['email'] !== $user->email;

                if ($normalizedUsername !== $user->username) {
                    $user->username_changed_at = Carbon::now();
                }

                $profileData = collect($validated)
                    ->except(['avatar', 'remove_avatar'])
                    ->all();

                if ($newAvatarPath !== null) {
                    $profileData['avatar_path'] = $newAvatarPath;
                } elseif ($removeAvatar) {
                    $profileData['avatar_path'] = null;
                }

                $user->fill($profileData);

                if ($emailChanged) {
                    $user->email_verified_at = null;
                }

                $user->save();
            });
        } catch (Throwable $e) {
            if ($newAvatarPath) {
                Storage::disk('public')->delete($newAvatarPath);
            }
            throw $e;
        }

        if ($avatarWasReplaced && $oldAvatarPath) {
            Storage::disk('public')->delete($oldAvatarPath);
        }
    }
}
