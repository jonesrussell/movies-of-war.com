<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Movie
 *
 * @property-read bool|null $is_watchlisted
 */
class MovieResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tmdb_id' => $this->tmdb_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'release_year' => $this->release_year,
            'release_date' => $this->release_date?->toDateString(),
            'synopsis' => $this->synopsis,
            'runtime' => $this->runtime,
            'country' => $this->country,
            'conflict' => $this->conflict,
            'poster_path' => $this->poster_path,
            'poster_url' => $this->poster_url,
            'trailer_url' => $this->trailer_url,
            'imdb_id' => $this->imdb_id,
            'tmdb_vote_average' => $this->tmdb_vote_average,
            'tmdb_vote_count' => $this->tmdb_vote_count,
            'director' => $this->director,
            'writers' => $this->writers,
            'production_status' => $this->production_status,
            'original_language' => $this->original_language,
            'budget' => $this->budget,
            'revenue' => $this->revenue,
            'cast' => $this->cast,
            'crew' => $this->crew,
            'is_upcoming' => $this->is_upcoming,
            'status' => $this->status->value,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'tags' => $this->whenLoaded('tags', function () {
                return TagResource::collection($this->tags)->resolve(request());
            }, []),
            'is_watchlisted' => $this->when(
                property_exists($this->resource, 'is_watchlisted') || isset($this->resource->is_watchlisted),
                fn () => $this->resource->is_watchlisted ?? false
            ),
        ];
    }
}
