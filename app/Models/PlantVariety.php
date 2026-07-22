<?php

namespace App\Models;

use App\Enums\CatalogOrigin;
use App\Enums\CatalogStatus;
use Database\Factories\PlantVarietyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $plant_species_id
 * @property CatalogOrigin $origin
 * @property CatalogStatus $catalog_status
 * @property int|null $created_by
 * @property string $name
 * @property string|null $breeder
 * @property int|null $year_introduced
 * @property string|null $description
 * @property Carbon|null $archived_at
 * @property Carbon|null $submitted_at
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property string|null $moderation_note
 * @property int|null $duplicate_of_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User|null $reviewer
 * @property-read PlantVariety|null $duplicateOf
 */
class PlantVariety extends Model
{
    /** @use HasFactory<PlantVarietyFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'breeder',
        'year_introduced',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'origin' => CatalogOrigin::class,
            'catalog_status' => CatalogStatus::class,
            'archived_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
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
     * Scope for catalog display: approved entries + own entries of any status.
     * Does NOT include foreign pending_review entries — use the moderation routes for those.
     * The caller is responsible for applying whereNull('archived_at') when needed.
     *
     * @param  Builder<PlantVariety>  $query
     */
    public function scopeCatalogVisibleTo(Builder $query, User $user): void
    {
        $query->where(function (Builder $q) use ($user): void {
            $q->where(fn (Builder $q2) => $q2->where('origin', CatalogOrigin::System->value)->where('catalog_status', CatalogStatus::Approved->value))
                ->orWhere(fn (Builder $q2) => $q2->where('origin', CatalogOrigin::User->value)->where('created_by', $user->id))
                ->orWhere(fn (Builder $q2) => $q2->where('origin', CatalogOrigin::User->value)->where('catalog_status', CatalogStatus::Approved->value));
        });
    }

    /**
     * Scope for varieties that can be used in new GardenPlantings:
     * approved entries (system or user), plus own private/pending_review entries.
     * Excludes rejected, archived, and soft-deleted entries.
     *
     * @param  Builder<PlantVariety>  $query
     */
    public function scopeSelectableForPlanting(Builder $query, User $user): void
    {
        $query->whereNull('archived_at')
            ->whereNull('deleted_at')
            ->where(fn (Builder $q) => $q
                ->where('catalog_status', CatalogStatus::Approved->value)
                ->orWhere(fn (Builder $q2) => $q2
                    ->where('created_by', $user->id)
                    ->whereIn('catalog_status', [
                        CatalogStatus::Private->value,
                        CatalogStatus::PendingReview->value,
                    ])
                )
            );
    }

    /**
     * @return HasMany<GardenPlanting, $this>
     */
    public function plantings(): HasMany
    {
        return $this->hasMany(GardenPlanting::class, 'plant_variety_id');
    }

    /**
     * @return BelongsTo<PlantSpecies, $this>
     */
    public function species(): BelongsTo
    {
        return $this->belongsTo(PlantSpecies::class, 'plant_species_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return BelongsTo<PlantVariety, $this>
     */
    public function duplicateOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'duplicate_of_id');
    }
}
