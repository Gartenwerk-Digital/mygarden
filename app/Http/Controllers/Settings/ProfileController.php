<?php

namespace App\Http\Controllers\Settings;

use App\Actions\UpdateUserProfile;
use App\Data\Countries;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('settings/profile', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'nextUsernameChangeAllowedAt' => $user->nextUsernameChangeAllowedAt()?->toDateString(),
            'countries' => Countries::LIST,
        ]);
    }

    public function update(ProfileUpdateRequest $request, UpdateUserProfile $action): RedirectResponse
    {
        $action->update(
            user: $request->user(),
            validated: $request->validated(),
            avatarFile: $request->hasFile('avatar') ? $request->file('avatar') : null,
            removeAvatar: (bool) ($request->validated()['remove_avatar'] ?? false),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profil aktualisiert.')]);

        return to_route('profile.edit');
    }

    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
