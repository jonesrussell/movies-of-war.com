<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Person
 */
class PersonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $movies = $this->whenLoaded('movies');
        $filmographyCast = [];
        $filmographyCrew = [];
        $knownFor = [];

        if ($movies) {
            $castEntries = [];
            $crewEntries = [];
            foreach ($this->movies as $movie) {
                $pivot = $movie->pivot;
                $entry = [
                    'movie_id' => $movie->id,
                    'movie_title' => $movie->title,
                    'movie_slug' => $movie->slug,
                    'release_year' => $movie->release_year,
                    'poster_url' => $movie->poster_url,
                ];
                if ($pivot->job === 'Actor') {
                    $castEntries[] = array_merge($entry, [
                        'character' => $pivot->character,
                        'cast_order' => $pivot->cast_order,
                    ]);
                } else {
                    $crewEntries[] = array_merge($entry, [
                        'job' => $pivot->job,
                    ]);
                }
            }
            usort($castEntries, fn (array $a, array $b) => ($a['cast_order'] ?? 999) <=> ($b['cast_order'] ?? 999));
            usort($crewEntries, fn (array $a, array $b) => ($b['release_year'] ?? 0) <=> ($a['release_year'] ?? 0));
            usort($castEntries, fn (array $a, array $b) => ($b['release_year'] ?? 0) <=> ($a['release_year'] ?? 0));

            $filmographyCast = $castEntries;
            $filmographyCrew = $crewEntries;
            $allByYear = array_merge($castEntries, $crewEntries);
            usort($allByYear, fn (array $a, array $b) => ($b['release_year'] ?? 0) <=> ($a['release_year'] ?? 0));
            $knownFor = array_slice($allByYear, 0, 5);
        }

        return [
            'id' => $this->id,
            'tmdb_id' => $this->tmdb_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'profile_path' => $this->profile_path,
            'biography' => $this->biography,
            'birthday' => $this->birthday?->toDateString(),
            'deathday' => $this->deathday?->toDateString(),
            'place_of_birth' => $this->place_of_birth,
            'also_known_as' => $this->also_known_as,
            'known_for' => $knownFor,
            'filmography_cast' => $filmographyCast,
            'filmography_crew' => $filmographyCrew,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
