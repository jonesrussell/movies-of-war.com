<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XTrendResult extends Model
{
    /** @use HasFactory<\Database\Factories\XTrendResultFactory> */
    use HasFactory;

    protected $fillable = [
        'trend_keyword_id',
        'tweet_id',
        'author_username',
        'content',
        'like_count',
        'retweet_count',
        'reply_count',
        'tweet_created_at',
        'url',
        'is_actioned',
        'actioned_at',
    ];

    protected function casts(): array
    {
        return [
            'tweet_created_at' => 'datetime',
            'is_actioned' => 'boolean',
            'actioned_at' => 'datetime',
            'like_count' => 'integer',
            'retweet_count' => 'integer',
            'reply_count' => 'integer',
        ];
    }

    public function keyword(): BelongsTo
    {
        return $this->belongsTo(XTrendKeyword::class, 'trend_keyword_id');
    }

    public function scopeActioned($query)
    {
        return $query->where('is_actioned', true);
    }

    public function scopeUnactioned($query)
    {
        return $query->where('is_actioned', false);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('tweet_created_at', '>=', now()->subDays($days));
    }

    public function markAsActioned(): void
    {
        $this->update([
            'is_actioned' => true,
            'actioned_at' => now(),
        ]);
    }
}
