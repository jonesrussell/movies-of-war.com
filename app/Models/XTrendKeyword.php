<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class XTrendKeyword extends Model
{
    /** @use HasFactory<\Database\Factories\XTrendKeywordFactory> */
    use HasFactory;

    const TYPE_HASHTAG = 'hashtag';

    const TYPE_KEYWORD = 'keyword';

    const TYPE_PHRASE = 'phrase';

    protected $fillable = [
        'keyword',
        'type',
        'is_active',
        'last_searched_at',
        'match_count',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_searched_at' => 'datetime',
            'match_count' => 'integer',
        ];
    }

    public function results(): HasMany
    {
        return $this->hasMany(XTrendResult::class, 'trend_keyword_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function markSearched(int $matchesFound = 0): void
    {
        $this->update([
            'last_searched_at' => now(),
            'match_count' => $this->match_count + $matchesFound,
        ]);
    }
}
