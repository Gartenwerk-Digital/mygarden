<?php

namespace App\Actions\Gardens;

use App\Models\Garden;

class ArchiveGarden
{
    public function handle(Garden $garden): Garden
    {
        if ($garden->archived_at === null) {
            $garden->forceFill(['archived_at' => now()])->save();
        }

        return $garden;
    }
}
