<?php

namespace App\Actions\Gardens;

use App\Models\Garden;

class UpdateGarden
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function handle(Garden $garden, array $validated): Garden
    {
        $garden->fill($validated)->save();

        return $garden;
    }
}
