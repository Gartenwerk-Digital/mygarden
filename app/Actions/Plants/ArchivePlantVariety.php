<?php

namespace App\Actions\Plants;

use App\Models\PlantVariety;

class ArchivePlantVariety
{
    public function handle(PlantVariety $variety): void
    {
        if ($variety->archived_at === null) {
            $variety->forceFill(['archived_at' => now()])->save();
        }
    }
}
