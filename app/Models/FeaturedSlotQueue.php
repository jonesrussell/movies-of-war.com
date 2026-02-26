<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SelectionMethod;
use App\Enums\SlotType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeaturedSlotQueue extends Model
{
    /** @use HasFactory<\Database\Factories\FeaturedSlotQueueFactory> */
    use HasFactory;

    protected $table = 'featured_slot_queue';

    protected $fillable = [
        'movie_id',
        'slot',
        'position',
        'selection_method',
        'scheduled_for',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'scheduled_for' => 'date',
            'slot' => SlotType::class,
            'selection_method' => SelectionMethod::class,
        ];
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    /**
     * @param  Builder<FeaturedSlotQueue>  $query
     * @return Builder<FeaturedSlotQueue>
     */
    public function scopeSlot(Builder $query, SlotType|string $slot): Builder
    {
        return $query->where('slot', $slot instanceof SlotType ? $slot->value : $slot);
    }

    /**
     * @param  Builder<FeaturedSlotQueue>  $query
     * @return Builder<FeaturedSlotQueue>
     */
    public function scopeNextUp(Builder $query): Builder
    {
        return $query->orderBy('position');
    }
}
