<?php

namespace App\Actions\Areas;

use App\Models\Area;
use App\Models\Garden;

class CreateArea
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function handle(Garden $garden, array $validated): Area
    {
        /** @var Area $area */
        $area = $garden->areas()->create($validated);

        return $area;
    }
}
