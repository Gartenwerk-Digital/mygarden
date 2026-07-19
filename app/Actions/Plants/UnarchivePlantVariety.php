<?php

namespace App\Actions\Plants;

use App\Models\PlantVariety;

class UnarchivePlantVariety
{
    public function handle(PlantVariety $variety): void
    {
        if ($variety->archived_at !== null) {
            $variety->forceFill(['archived_at' => null])->save();
        }
    }
}
