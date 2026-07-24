<?php

namespace App\Models;

use App\Enums\PlantingStatus;
use App\Enums\PlantingUnit;
use Database\Factories\GardenPlantingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $garden_id
 * @property int|null $area_id
 * @property int $plant_species_id
 * @property int|null $plant_variety_id
 * @property PlantingStatus $status
 * @property Carbon|null $removed_at
 * @property string|null $quantity
 * @property PlantingUnit|null $quantity_unit
 * @property string|null $nickname
 * @property string|null $notes
 * @property string|null $planted_at
 * @property string|null $acquired_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class GardenPlanting extends Model
{
    /** @use HasFactory<GardenPlantingFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'area_id',
        'plant_species_id',
        'plant_variety_id',
        'status',
        'quantity',
        'quantity_unit',
        'nickname',
        'notes',
        'planted_at',
        'acquired_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PlantingStatus::class,
            'quantity_unit' => PlantingUnit::class,
            'quantity' => 'decimal:2',
            'removed_at' => 'datetime',
            'planted_at' => 'date:Y-m-d',
            'acquired_at' => 'date:Y-m-d',
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
     * withTrashed: Archivierte oder soft-deleted Areas bleiben darstellbar.
     *
     * @return BelongsTo<Area, $this>
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class)->withTrashed();
    }

    /**
     * withTrashed: Archivierte oder soft-deleted Species bleiben darstellbar.
     *
     * @return BelongsTo<PlantSpecies, $this>
     */
    public function species(): BelongsTo
    {
        return $this->belongsTo(PlantSpecies::class, 'plant_species_id')->withTrashed();
    }

    /**
     * withTrashed: Archivierte oder soft-deleted Varieties bleiben darstellbar.
     *
     * @return BelongsTo<PlantVariety, $this>
     */
    public function variety(): BelongsTo
    {
        return $this->belongsTo(PlantVariety::class, 'plant_variety_id')->withTrashed();
    }

    /**
     * @return HasMany<PlantingEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(PlantingEntry::class);
    }
}
