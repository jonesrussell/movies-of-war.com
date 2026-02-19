<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use JonesRussell\NorthCloud\Models\Article as BaseArticle;

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
        'articleable_type',
        'articleable_id',
    ];

    public function tags(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        $tagModel = config('northcloud.models.tag');

        return $this->belongsToMany($tagModel, 'article_tag', 'article_id', 'tag_id')
            ->withTimestamps()
            ->withPivot('confidence');
    }

    public function scopeWarEra($query, string $era)
    {
        return $query->where('war_era', $era);
    }
}
