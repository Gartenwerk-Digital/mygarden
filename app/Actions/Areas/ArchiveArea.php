<?php

namespace App\Actions\Areas;

use App\Models\Area;
use Illuminate\Validation\ValidationException;

class ArchiveArea
{
    public function handle(Area $area): Area
    {
        if ($area->children()->exists()) {
            throw ValidationException::withMessages([
                'children' => __('Dieser Bereich enthält noch Unterbereiche. Bitte zuerst alle Unterbereiche löschen oder einem anderen übergeordneten Bereich zuordnen.'),
            ]);
        }

        if ($area->archived_at === null) {
            $area->forceFill(['archived_at' => now()])->save();
        }

        return $area;
    }
}
