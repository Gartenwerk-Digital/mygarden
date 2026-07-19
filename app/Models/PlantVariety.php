<?php

namespace App\Models;

use App\Enums\CatalogOrigin;
use Database\Factories\PlantVarietyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $plant_species_id
 * @property CatalogOrigin $origin
 * @property int|null $created_by
 * @property string $name
 * @property string|null $breeder
 * @property int|null $year_introduced
 * @property string|null $description
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class PlantVariety extends Model
{
    /** @use HasFactory<PlantVarietyFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'origin',
        'created_by',
        'name',
        'breeder',
        'year_introduced',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'origin' => CatalogOrigin::class,
            'archived_at' => 'datetime',
        ];
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
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
}
