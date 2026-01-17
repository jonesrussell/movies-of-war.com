<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class XCuratedPost extends Model
{
    /** @use HasFactory<\Database\Factories\XCuratedPostFactory> */
    use HasFactory;

    protected $fillable = [
        'tweet_id',
        'author_username',
        'content',
        'media_urls',
        'like_count',
        'retweet_count',
        'discovered_at',
        'is_featured',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'media_urls' => 'array',
            'discovered_at' => 'datetime',
            'is_featured' => 'boolean',
            'like_count' => 'integer',
            'retweet_count' => 'integer',
        ];
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('discovered_at', '>=', now()->subDays($days));
    }

    public function scopeHighEngagement($query, int $minLikes = 10)
    {
        return $query->where('like_count', '>=', $minLikes)
            ->orWhere('retweet_count', '>=', 5);
    }

    public function getTweetUrl(): string
    {
        return "https://x.com/i/web/status/{$this->tweet_id}";
    }

    public function toggleFeatured(): void
    {
        $this->update(['is_featured' => ! $this->is_featured]);
    }
}
