<?php

namespace App\Actions\Areas;

use App\Models\Area;

class UpdateArea
{
    public function handle(Area $area, array $validated): Area
    {
        $area->fill($validated)->save();

        return $area;
    }
}
