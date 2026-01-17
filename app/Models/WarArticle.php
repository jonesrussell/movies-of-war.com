<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use JonesRussell\LaravelRedisArticles\Models\Article as BaseArticle;

class WarArticle extends BaseArticle
{
    use HasFactory;

    protected $table = 'articles';

    protected $fillable = [
        'news_source_id',
        'author_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'url',
        'external_id',
        'image_url',
        'author',
        'status',
        'published_at',
        'crawled_at',
        'metadata',
        'view_count',
        'is_featured',
        'war_era',
    ];

    /**
     * Many-to-many relationship with movies.
     */
    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class, 'article_movie')
            ->withTimestamps()
            ->withPivot('confidence');
    }

    /**
     * Scope articles by war era.
     */
    public function scopeWarEra($query, string $era)
    {
        return $query->where('war_era', $era);
    }

    /**
     * Get articles with related movies.
     */
    public function scopeWithMovies($query)
    {
        return $query->with('movies');
    }
}
