<?php

namespace App\Actions\Gardens;

use App\Models\Garden;

class UnarchiveGarden
{
    public function handle(Garden $garden): Garden
    {
        if ($garden->archived_at !== null) {
            $garden->forceFill(['archived_at' => null])->save();
        }

        return $garden;
    }
}
