<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property int $user_id
 * @property int $movie_id
 * @property float $rating
 * @property string|null $title
 * @property string $content
 * @property bool $has_spoilers
 * @property bool $is_published
 * @property int $helpful_count
 * @property int $comments_count
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Review extends Model
{
    /** @use HasFactory<\Database\Factories\ReviewFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'movie_id',
        'rating',
        'title',
        'content',
        'has_spoilers',
        'is_published',
        'helpful_count',
        'comments_count',
    ];

    protected function casts(): array
    {
        return [
            'has_spoilers' => 'boolean',
            'is_published' => 'boolean',
            'rating' => 'decimal:1',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ReviewComment::class);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * @param  Builder<Review>  $query
     * @return Builder<Review>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * @param  Builder<Review>  $query
     * @return Builder<Review>
     */
    public function scopeWithoutSpoilers(Builder $query): Builder
    {
        return $query->where('has_spoilers', false);
    }

    /**
     * @param  Builder<Review>  $query
     * @return Builder<Review>
     */
    public function scopeWithSpoilers(Builder $query): Builder
    {
        return $query->where('has_spoilers', true);
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    public function getStarsAttribute(): string
    {
        $rating = isset($this->attributes['rating'])
            ? (float) $this->attributes['rating']
            : (float) $this->rating;
        $fullStars = (int) floor($rating);

        return str_repeat('★', $fullStars).str_repeat('☆', 4 - $fullStars);
    }

    public function getIsEditedAttribute(): bool
    {
        return $this->updated_at->gt($this->created_at);
    }
}
