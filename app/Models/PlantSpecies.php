<?php

namespace App\Models;

use App\Enums\CatalogOrigin;
use App\Enums\CatalogStatus;
use App\Enums\MoistureLevel;
use App\Enums\PlantCategory;
use App\Enums\PlantLifecycle;
use App\Enums\SoilType;
use App\Enums\SunExposure;
use Database\Factories\PlantSpeciesFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property CatalogOrigin $origin
 * @property CatalogStatus $catalog_status
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
            'catalog_status' => CatalogStatus::class,
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

    public function isVisibleTo(User $user): bool
    {
        if ($this->archived_at !== null) {
            return $user->id === $this->created_by;
        }

        if ($this->origin === CatalogOrigin::System) {
            return $this->catalog_status === CatalogStatus::Approved;
        }

        if ($user->id === $this->created_by) {
            return true;
        }

        if ($this->catalog_status === CatalogStatus::Approved) {
            return true;
        }

        if ($this->catalog_status === CatalogStatus::PendingReview) {
            return $user->canModerateCatalog();
        }

        return false;
    }

    /**
     * @param  Builder<PlantSpecies>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $query->where(function (Builder $q) use ($user): void {
            $q->where(fn (Builder $q2) => $q2->where('origin', CatalogOrigin::System->value)->where('catalog_status', CatalogStatus::Approved->value))
                ->orWhere(fn (Builder $q2) => $q2->where('origin', CatalogOrigin::User->value)->where('created_by', $user->id))
                ->orWhere(fn (Builder $q2) => $q2->where('origin', CatalogOrigin::User->value)->where('catalog_status', CatalogStatus::Approved->value));

            if ($user->canModerateCatalog()) {
                $q->orWhere(fn (Builder $q2) => $q2->where('origin', CatalogOrigin::User->value)->where('catalog_status', CatalogStatus::PendingReview->value));
            }
        });
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
