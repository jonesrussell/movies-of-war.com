<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * @property-read int $id
 * @property int $tmdb_id
 * @property string $name
 * @property string $slug
 * @property string|null $profile_path
 * @property string|null $biography
 * @property \Illuminate\Support\Carbon|null $birthday
 * @property \Illuminate\Support\Carbon|null $deathday
 * @property string|null $place_of_birth
 * @property array|null $also_known_as
 * @property \Illuminate\Support\Carbon|null $tmdb_last_synced_at
 */
class Person extends Model
{
    /** @use HasFactory<\Database\Factories\PersonFactory> */
    use HasFactory;

    protected $fillable = [
        'tmdb_id',
        'name',
        'slug',
        'profile_path',
        'biography',
        'birthday',
        'deathday',
        'place_of_birth',
        'also_known_as',
        'tmdb_last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'deathday' => 'date',
            'also_known_as' => 'array',
            'tmdb_last_synced_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Person $person): void {
            if (empty($person->slug) && ! empty($person->name) && ! empty($person->tmdb_id)) {
                $person->slug = Str::slug($person->name).'-'.$person->tmdb_id;
            }
        });
    }

    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class, 'movie_person')
            ->withTimestamps()
            ->withPivot('job', 'character', 'department', 'cast_order');
    }

    /**
     * People that are stale for TMDB refresh (cadence + max-age cap).
     *
     * @param  Builder<Person>  $query
     * @return Builder<Person>
     */
    public function scopeStaleForTmdbRefresh(Builder $query, int $limit = 50, ?int $maxAgeDays = null): Builder
    {
        $maxAgeDays ??= config('tmdb.refresh_people_max_age_days', 180);

        return $query->where(function (Builder $q) use ($maxAgeDays): void {
            $q->whereNull('tmdb_last_synced_at')
                ->orWhere('tmdb_last_synced_at', '<', now()->subDays($maxAgeDays));
        })
            ->orderByRaw('tmdb_last_synced_at IS NULL DESC')
            ->orderBy('tmdb_last_synced_at')
            ->limit($limit);
    }
}
