<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TagType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * @property-read int $id
 * @property string $name
 * @property string $slug
 * @property TagType $type
 * @property string|null $color
 * @property string|null $description
 * @property int $article_count
 */
class Tag extends Model
{
    /** @use HasFactory<\Database\Factories\TagFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'color',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'type' => TagType::class,
            'article_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Tag $tag): void {
            $tag->slug ??= Str::slug($tag->name);
            $tag->type ??= TagType::Genre;
        });
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class)->withTimestamps();
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(WarArticle::class, 'article_tag')
            ->withTimestamps()
            ->withPivot('confidence');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * @param  Builder<Tag>  $query
     * @return Builder<Tag>
     */
    public function scopeOfType(Builder $query, TagType $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * @param  Builder<Tag>  $query
     * @return Builder<Tag>
     */
    public function scopeGenres(Builder $query): Builder
    {
        return $query->ofType(TagType::Genre);
    }

    /**
     * @param  Builder<Tag>  $query
     * @return Builder<Tag>
     */
    public function scopeThemes(Builder $query): Builder
    {
        return $query->ofType(TagType::Theme);
    }

    /**
     * @param  Builder<Tag>  $query
     * @return Builder<Tag>
     */
    public function scopeEras(Builder $query): Builder
    {
        return $query->ofType(TagType::Era);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    public function isGenre(): bool
    {
        return $this->type === TagType::Genre;
    }

    public function isTheme(): bool
    {
        return $this->type === TagType::Theme;
    }

    public function isEra(): bool
    {
        return $this->type === TagType::Era;
    }
}
