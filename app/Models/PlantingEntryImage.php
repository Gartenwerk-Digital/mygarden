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
}
