<?php

namespace App\Models;

use App\Enums\AreaType;
use App\Enums\MoistureLevel;
use App\Enums\SoilType;
use App\Enums\SunExposure;
use Database\Factories\AreaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $garden_id
 * @property int|null $parent_id
 * @property string $name
 * @property AreaType $type
 * @property string|null $description
 * @property string|null $size_sqm
 * @property SunExposure|null $sun_exposure
 * @property SoilType|null $soil_type
 * @property MoistureLevel|null $moisture_level
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Area extends Model
{
    /** @use HasFactory<AreaFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'parent_id',
        'name',
        'type',
        'description',
        'size_sqm',
        'sun_exposure',
        'soil_type',
        'moisture_level',
    ];

    protected function casts(): array
    {
        return [
            'type' => AreaType::class,
            'sun_exposure' => SunExposure::class,
            'soil_type' => SoilType::class,
            'moisture_level' => MoistureLevel::class,
            'size_sqm' => 'decimal:2',
            'archived_at' => 'datetime',
        ];
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * @return BelongsTo<Garden, $this>
     */
    public function garden(): BelongsTo
    {
        return $this->belongsTo(Garden::class);
    }

    /**
     * @return BelongsTo<Area, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'parent_id');
    }

    /**
     * @return HasMany<Area, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Area::class, 'parent_id');
    }

    /**
     * @return HasMany<GardenPlanting, $this>
     */
    public function plantings(): HasMany
    {
        return $this->hasMany(GardenPlanting::class);
    }
}
