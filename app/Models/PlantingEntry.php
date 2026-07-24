<?php

namespace App\Models;

use App\Enums\EntryType;
use Database\Factories\PlantingEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $garden_planting_id
 * @property EntryType $entry_type
 * @property string|null $notes
 * @property Carbon $occurred_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PlantingEntry extends Model
{
    /** @use HasFactory<PlantingEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'entry_type',
        'notes',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'entry_type' => EntryType::class,
            'occurred_at' => 'date:Y-m-d',
        ];
    }

    /**
     * @return BelongsTo<GardenPlanting, $this>
     */
    public function planting(): BelongsTo
    {
        return $this->belongsTo(GardenPlanting::class, 'garden_planting_id');
    }

    /**
     * @return HasMany<PlantingEntryImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(PlantingEntryImage::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * Compact array representation for Inertia timeline props.
     * Excludes internal storage paths and original filenames.
     *
     * @return array{id: int, garden_planting_id: int, entry_type: string, notes: string|null, occurred_at: string, created_at: string|null, images: list<array{id: int, planting_entry_id: int, mime_type: string, size: int, sort_order: int, created_at: string|null}>}
     */
    public function toTimelineArray(): array
    {
        return [
            'id' => $this->id,
            'garden_planting_id' => $this->garden_planting_id,
            'entry_type' => $this->entry_type->value,
            'notes' => $this->notes,
            'occurred_at' => $this->occurred_at->format('Y-m-d'),
            'created_at' => $this->created_at?->toIso8601String(),
            'images' => array_values(
                $this->images
                    ->map(fn (PlantingEntryImage $img): array => $img->toTimelineArray())
                    ->all()
            ),
        ];
    }
}
