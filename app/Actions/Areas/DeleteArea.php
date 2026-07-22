<?php

namespace App\Actions\Areas;

use App\Models\Area;
use Illuminate\Validation\ValidationException;

class DeleteArea
{
    public function handle(Area $area): void
    {
        if ($area->children()->exists()) {
            throw ValidationException::withMessages([
                'children' => __('Dieser Bereich enthält noch Unterbereiche. Bitte zuerst alle Unterbereiche löschen oder einem anderen übergeordneten Bereich zuordnen.'),
            ]);
        }

        if ($area->plantings()->whereNull('deleted_at')->exists()) {
            throw ValidationException::withMessages([
                'plantings' => __('Dieser Bereich enthält noch Pflanzungen. Bitte zuerst alle Pflanzungen entfernen oder einem anderen Bereich zuordnen.'),
            ]);
        }

        $area->delete();
    }
}
