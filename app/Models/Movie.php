<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MovieStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property-read int $id
 * @property int|null $tmdb_id
 * @property string $title
 * @property string $slug
 * @property MovieStatus $status
 * @property int|null $release_year
 * @property \Illuminate\Support\Carbon|null $release_date
 * @property string|null $synopsis
 * @property int|null $runtime
 * @property string|null $country
 * @property string|null $conflict
 * @property string|null $poster_path
 * @property string|null $poster_url
 * @property string|null $trailer_url
 * @property string|null $imdb_id
 * @property float|null $tmdb_vote_average
 * @property int|null $tmdb_vote_count
 * @property string|null $director
 * @property array|null $writers
 * @property bool $is_upcoming
 */
class Movie extends Model
{
    /** @use HasFactory<\Database\Factories\MovieFactory> */
    use HasFactory;

    /**
     * @deprecated Use MovieStatus::Draft instead
     */
    public const STATUS_DRAFT = 'draft';

    /**
     * @deprecated Use MovieStatus::Published instead
     */
    public const STATUS_PUBLISHED = 'published';

    /**
     * @deprecated Use MovieStatus::Archived instead
     */
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'tmdb_id',
        'title',
        'slug',
        'release_year',
        'release_date',
        'synopsis',
        'runtime',
        'country',
        'conflict',
        'poster_path',
        'poster_url',
        'trailer_url',
        'imdb_id',
        'tmdb_vote_average',
        'tmdb_vote_count',
        'director',
        'writers',
        'is_upcoming',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'release_date' => 'date',
            'is_upcoming' => 'boolean',
            'status' => MovieStatus::class,
            'writers' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Movie $movie): void {
            $movie->slug ??= Str::slug($movie->title);
            $movie->status ??= MovieStatus::Draft;
        });
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    public function featuredSlots(): HasMany
    {
        return $this->hasMany(FeaturedSlot::class);
    }

    public function watchlistedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'watchlists')->withTimestamps();
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(WarArticle::class, 'article_movie')
            ->withTimestamps()
            ->withPivot('confidence');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * @param  Builder<Movie>  $query
     * @return Builder<Movie>
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', MovieStatus::Draft);
    }

    /**
     * @param  Builder<Movie>  $query
     * @return Builder<Movie>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', MovieStatus::Published);
    }

    /**
     * @param  Builder<Movie>  $query
     * @return Builder<Movie>
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', MovieStatus::Archived);
    }

    /**
     * @param  Builder<Movie>  $query
     * @return Builder<Movie>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('is_upcoming', true);
    }

    /**
     * @param  Builder<Movie>  $query
     * @return Builder<Movie>
     */
    public function scopeReleased(Builder $query): Builder
    {
        return $query->where('is_upcoming', false);
    }

    // =========================================================================
    // State Checks
    // =========================================================================

    public function isDraft(): bool
    {
        return $this->status === MovieStatus::Draft;
    }

    public function isPublished(): bool
    {
        return $this->status === MovieStatus::Published;
    }

    public function isArchived(): bool
    {
        return $this->status === MovieStatus::Archived;
    }

    // =========================================================================
    // State Transitions
    // =========================================================================

    public function publish(): void
    {
        $this->update(['status' => MovieStatus::Published]);
    }

    public function unpublish(): void
    {
        $this->update(['status' => MovieStatus::Draft]);
    }

    public function archive(): void
    {
        $this->update(['status' => MovieStatus::Archived]);
    }

    public function restore(): void
    {
        $this->update(['status' => MovieStatus::Draft]);
    }
}
