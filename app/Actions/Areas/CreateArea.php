<?php

namespace App\Actions\Areas;

use App\Models\Area;
use App\Models\Garden;

class CreateArea
{
    public function handle(Garden $garden, array $validated): Area
    {
        return $garden->areas()->create($validated);
    }
}
