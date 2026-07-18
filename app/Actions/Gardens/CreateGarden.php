<?php

namespace App\Actions\Gardens;

use App\Models\Garden;
use App\Models\User;

class CreateGarden
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function handle(User $user, array $validated): Garden
    {
        return $user->gardens()->create($validated);
    }
}
