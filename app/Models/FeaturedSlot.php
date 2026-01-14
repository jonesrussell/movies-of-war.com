<?php

namespace App\Models;

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

    public function scopeActive($query)
    {
        return $query; // All slots are active
    }

    public function scopeSlot($query, string $slot)
    {
        return $query->where('slot', $slot);
    }
}
