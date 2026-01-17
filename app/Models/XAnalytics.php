<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XAnalytics extends Model
{
    /** @use HasFactory<\Database\Factories\XAnalyticsFactory> */
    use HasFactory;

    protected $fillable = [
        'x_post_id',
        'tweet_id',
        'impressions',
        'likes',
        'retweets',
        'replies',
        'bookmarks',
        'quotes',
        'profile_clicks',
        'link_clicks',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'impressions' => 'integer',
            'likes' => 'integer',
            'retweets' => 'integer',
            'replies' => 'integer',
            'bookmarks' => 'integer',
            'quotes' => 'integer',
            'profile_clicks' => 'integer',
            'link_clicks' => 'integer',
        ];
    }

    public function xPost(): BelongsTo
    {
        return $this->belongsTo(XPost::class);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('recorded_at', 'desc');
    }

    public function scopeForTweet($query, string $tweetId)
    {
        return $query->where('tweet_id', $tweetId);
    }
}
