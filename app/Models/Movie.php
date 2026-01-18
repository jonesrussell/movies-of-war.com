<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Movie extends Model
{
    /** @use HasFactory<\Database\Factories\MovieFactory> */
    use HasFactory;

    const STATUS_DRAFT = 'draft';

    const STATUS_PUBLISHED = 'published';

    const STATUS_ARCHIVED = 'archived';

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
        'is_upcoming',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'release_date' => 'date',
            'is_upcoming' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Movie $movie) {
            if (empty($movie->slug)) {
                $movie->slug = Str::slug($movie->title);
            }
        });
    }

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

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeArchived($query)
    {
        return $query->where('status', self::STATUS_ARCHIVED);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('is_upcoming', true);
    }

    public function scopeReleased($query)
    {
        return $query->where('is_upcoming', false);
    }
}
