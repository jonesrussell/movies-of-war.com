<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeaturedSlot extends Model
{
    /** @use HasFactory<\Database\Factories\FeaturedSlotFactory> */
    use HasFactory;

    protected $fillable = [
        'movie_id',
        'slot',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    /**
     * @param  Builder<FeaturedSlot>  $query
     * @return Builder<FeaturedSlot>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query; // All slots are active
    }

    /**
     * @param  Builder<FeaturedSlot>  $query
     * @return Builder<FeaturedSlot>
     */
    public function scopeSlot(Builder $query, string $slot): Builder
    {
        return $query->where('slot', $slot);
    }
}
