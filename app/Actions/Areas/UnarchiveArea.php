<?php

namespace App\Actions\Areas;

use App\Models\Area;

class UnarchiveArea
{
    public function handle(Area $area): Area
    {
        if ($area->archived_at !== null) {
            $area->forceFill(['archived_at' => null])->save();
        }

        return $area;
    }
}
