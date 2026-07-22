<?php

namespace App\Models;

use App\Enums\GardenVisibility;
use Database\Factories\GardenFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $description
 * @property GardenVisibility $visibility
 * @property string|null $size_sqm
 * @property string|null $country
 * @property string|null $region
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Garden extends Model
{
    /** @use HasFactory<GardenFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'visibility',
        'size_sqm',
        'country',
        'region',
    ];

    protected function casts(): array
    {
        return [
            'visibility' => GardenVisibility::class,
            'size_sqm' => 'decimal:2',
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Area, $this>
     */
    public function areas(): HasMany
    {
        return $this->hasMany(Area::class);
    }

    /**
     * @return HasMany<GardenPlanting, $this>
     */
    public function plantings(): HasMany
    {
        return $this->hasMany(GardenPlanting::class);
    }
}
