<?php

namespace App\Models;

use App\Enums\CatalogOrigin;
use App\Enums\MoistureLevel;
use App\Enums\PlantCategory;
use App\Enums\PlantLifecycle;
use App\Enums\SoilType;
use App\Enums\SunExposure;
use Database\Factories\PlantSpeciesFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property CatalogOrigin $origin
 * @property int|null $created_by
 * @property string $common_name
 * @property string|null $botanical_name
 * @property string|null $alternate_name
 * @property PlantCategory $category
 * @property string|null $description
 * @property PlantLifecycle|null $lifecycle
 * @property string|null $hardiness_note
 * @property SunExposure[]|null $sun_exposures
 * @property SoilType[]|null $soil_types
 * @property MoistureLevel[]|null $moisture_levels
 * @property int|null $height_min_cm
 * @property int|null $height_max_cm
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class PlantSpecies extends Model
{
    /** @use HasFactory<PlantSpeciesFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'origin',
        'created_by',
        'common_name',
        'botanical_name',
        'alternate_name',
        'category',
        'description',
        'lifecycle',
        'hardiness_note',
        'sun_exposures',
        'soil_types',
        'moisture_levels',
        'height_min_cm',
        'height_max_cm',
    ];

    protected function casts(): array
    {
        return [
            'origin' => CatalogOrigin::class,
            'category' => PlantCategory::class,
            'lifecycle' => PlantLifecycle::class,
            'sun_exposures' => 'array',
            'soil_types' => 'array',
            'moisture_levels' => 'array',
            'archived_at' => 'datetime',
        ];
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<PlantVariety, $this>
     */
    public function varieties(): HasMany
    {
        return $this->hasMany(PlantVariety::class);
    }
}
