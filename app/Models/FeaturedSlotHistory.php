<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeaturedSlotHistory extends Model
{
    /** @use HasFactory<\Database\Factories\FeaturedSlotHistoryFactory> */
    use HasFactory;

    protected $table = 'featured_slot_history';

    protected $fillable = [
        'movie_id',
        'slot',
        'selection_method',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    /**
     * @param  Builder<FeaturedSlotHistory>  $query
     * @return Builder<FeaturedSlotHistory>
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->whereNull('ended_at');
    }

    /**
     * @param  Builder<FeaturedSlotHistory>  $query
     * @return Builder<FeaturedSlotHistory>
     */
    public function scopeSlot(Builder $query, string $slot): Builder
    {
        return $query->where('slot', $slot);
    }
}
