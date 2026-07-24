<?php

namespace App\Models;

use Database\Factories\PlantingEntryImageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $planting_entry_id
 * @property string $path
 * @property string $mime_type
 * @property int $size
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PlantingEntryImage extends Model
{
    /** @use HasFactory<PlantingEntryImageFactory> */
    use HasFactory;

    protected $fillable = [
        'path',
        'mime_type',
        'size',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<PlantingEntry, $this>
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(PlantingEntry::class, 'planting_entry_id');
    }

    /**
     * Compact array representation for Inertia timeline props.
     * Excludes the internal storage path.
     *
     * @return array{id: int, planting_entry_id: int, mime_type: string, size: int, sort_order: int, created_at: string|null}
     */
    public function toTimelineArray(): array
    {
        return [
            'id' => $this->id,
            'planting_entry_id' => $this->planting_entry_id,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
