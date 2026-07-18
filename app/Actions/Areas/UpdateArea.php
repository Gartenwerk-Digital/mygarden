<?php

namespace App\Actions\Areas;

use App\Models\Area;

class UpdateArea
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function handle(Area $area, array $validated): Area
    {
        $area->fill($validated)->save();

        return $area;
    }
}
