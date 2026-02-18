<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MovieStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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
 * @property string|null $production_status
 * @property string|null $original_language
 * @property int|null $budget
 * @property int|null $revenue
 * @property array|null $cast
 * @property array|null $crew
 * @property \Illuminate\Support\Carbon|null $tmdb_last_synced_at
 * @property bool $is_upcoming
 * @property-read float|null $user_rating_average
 * @property-read int $user_rating_count
 */
class Movie extends Model
{
    /** @use HasFactory<\Database\Factories\MovieFactory> */
    use HasFactory;

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
        'production_status',
        'original_language',
        'budget',
        'revenue',
        'cast',
        'crew',
        'tmdb_last_synced_at',
        'is_upcoming',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'release_date' => 'date',
            'tmdb_last_synced_at' => 'datetime',
            'is_upcoming' => 'boolean',
            'status' => MovieStatus::class,
            'writers' => 'array',
            'cast' => 'array',
            'crew' => 'array',
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

    public function articles(): MorphMany
    {
        return $this->morphMany(
            config('northcloud.models.article', \JonesRussell\NorthCloud\Models\Article::class),
            'articleable'
        );
    }

    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'movie_person')
            ->withTimestamps()
            ->withPivot('job', 'character', 'department', 'cast_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Average rating from published user reviews (0–4 scale).
     */
    public function getUserRatingAverageAttribute(): ?float
    {
        $avg = $this->reviews()->published()->avg('rating');

        return $avg !== null ? (float) $avg : null;
    }

    /**
     * Count of published user reviews.
     */
    public function getUserRatingCountAttribute(): int
    {
        return $this->reviews()->published()->count();
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

    /**
     * Movies that are stale for TMDB refresh (cadence + max-age cap).
     *
     * @param  Builder<Movie>  $query
     * @return Builder<Movie>
     */
    public function scopeStaleForTmdbRefresh(Builder $query, int $limit = 50, ?int $maxAgeDays = null): Builder
    {
        $maxAgeDays ??= config('tmdb.refresh_max_age_days', 120);
        $cadence = config('tmdb.refresh_cadence_days', ['popular' => 7, 'normal' => 30, 'obscure' => 90]);
        $popularThreshold = config('tmdb.refresh_popular_vote_count', 10_000);
        $obscureThreshold = config('tmdb.refresh_obscure_vote_count', 100);

        return $query->whereNotNull('tmdb_id')
            ->where(function (Builder $q) use ($maxAgeDays, $cadence, $popularThreshold, $obscureThreshold): void {
                $q->whereNull('tmdb_last_synced_at')
                    ->orWhere('tmdb_last_synced_at', '<', now()->subDays($maxAgeDays))
                    ->orWhere(function (Builder $q2) use ($cadence, $popularThreshold): void {
                        $q2->where('tmdb_vote_count', '>', $popularThreshold)
                            ->where('tmdb_last_synced_at', '<', now()->subDays($cadence['popular']));
                    })
                    ->orWhere(function (Builder $q2) use ($cadence, $obscureThreshold): void {
                        $q2->where('tmdb_vote_count', '<', $obscureThreshold)
                            ->where('tmdb_last_synced_at', '<', now()->subDays($cadence['obscure']));
                    })
                    ->orWhere(function (Builder $q2) use ($cadence, $obscureThreshold, $popularThreshold): void {
                        $q2->where(function (Builder $q3) use ($obscureThreshold, $popularThreshold): void {
                            $q3->whereBetween('tmdb_vote_count', [$obscureThreshold, $popularThreshold])
                                ->orWhereNull('tmdb_vote_count');
                        })
                            ->where('tmdb_last_synced_at', '<', now()->subDays($cadence['normal']));
                    });
            })
            ->orderByRaw('tmdb_last_synced_at IS NULL DESC')
            ->orderBy('tmdb_last_synced_at')
            ->limit($limit);
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
