<?php

namespace App\Models;

use Database\Factories\GardenImageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $garden_id
 * @property string $path
 * @property string $mime_type
 * @property int $size
 * @property int $sort_order
 * @property bool|null $is_cover
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class GardenImage extends Model
{
    /** @use HasFactory<GardenImageFactory> */
    use HasFactory;

    protected $fillable = [
        'path',
        'mime_type',
        'size',
        'sort_order',
        'is_cover',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'sort_order' => 'integer',
            'is_cover' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Garden, $this>
     */
    public function garden(): BelongsTo
    {
        return $this->belongsTo(Garden::class);
    }

    /**
     * Compact array representation for Inertia props.
     * Excludes the internal storage path.
     *
     * @return array{id: int, garden_id: int, mime_type: string, size: int, sort_order: int, is_cover: bool|null, created_at: string|null}
     */
    public function toInertiaArray(): array
    {
        return [
            'id' => $this->id,
            'garden_id' => $this->garden_id,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'sort_order' => $this->sort_order,
            'is_cover' => $this->is_cover,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
