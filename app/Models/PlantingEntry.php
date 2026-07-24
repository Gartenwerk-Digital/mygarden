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
 * @property string $occurred_at
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
}
