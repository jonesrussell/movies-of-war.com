<?php

namespace JonesRussell\NorthcloudLaravel\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use JonesRussell\NorthcloudLaravel\Contracts\ArticleModel;

abstract class Article extends Model implements ArticleModel
{
    use HasFactory, SoftDeletes;

    // Status constants
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

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
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'crawled_at' => 'datetime',
            'metadata' => 'array',
            'is_featured' => 'boolean',
        ];
    }

    public function newsSource(): BelongsTo
    {
        $newsSourceModel = config('northcloud.models.news_source');

        return $this->belongsTo($newsSourceModel);
    }

    public function tags(): BelongsToMany
    {
        $tagModel = config('northcloud.models.tag');

        return $this->belongsToMany($tagModel, 'article_tag', 'article_id', 'tag_id')
            ->withTimestamps()
            ->withPivot('confidence');
    }

    public function scopeDraft(Builder $query): void
    {
        $query->where('status', self::STATUS_DRAFT);
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('status', self::STATUS_PUBLISHED)
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at');
    }

    public function scopeArchived(Builder $query): void
    {
        $query->where('status', self::STATUS_ARCHIVED);
    }

    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    public function scopeWithTag(Builder $query, string $tagSlug): void
    {
        $query->whereHas('tags', function (Builder $q) use ($tagSlug) {
            $q->where('slug', $tagSlug);
        });
    }

    public function scopeSearch(Builder $query, string $searchTerm): void
    {
        $query->where(function ($q) use ($searchTerm) {
            $q->where('title', 'like', "%{$searchTerm}%")
                ->orWhere('excerpt', 'like', "%{$searchTerm}%")
                ->orWhere('content', 'like', "%{$searchTerm}%");
        });
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    // Implement ArticleModel contract methods
    public function getExternalId(): string
    {
        return $this->external_id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    public function markAsPublished(): void
    {
        $this->update([
            'status' => self::STATUS_PUBLISHED,
            'published_at' => $this->published_at ?? now(),
        ]);
    }

    public function markAsArchived(): void
    {
        $this->update(['status' => self::STATUS_ARCHIVED]);
    }
}
