<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\Tmdb\TmdbMovieData;
use App\Models\Movie;
use App\Models\Person;
use Illuminate\Support\Str;

class PersonSyncService
{
    /**
     * Sync people and movie_person pivot from movie DTO. Updates Movie cast/crew JSON with slugs.
     */
    public function syncFromMovieDto(Movie $movie, TmdbMovieData $dto): array
    {
        $pivotRows = [];
        $castWithSlug = [];
        $crewWithSlug = [];

        foreach ($dto->cast as $member) {
            $person = Person::firstOrCreate(
                ['tmdb_id' => $member['tmdb_id']],
                [
                    'name' => $member['name'],
                    'profile_path' => $member['profile_path'],
                    'slug' => Str::slug($member['name']).'-'.$member['tmdb_id'],
                ]
            );
            $key = $person->id.'|Actor|'.($member['character'] ?? '');
            if (! isset($pivotRows[$key])) {
                $pivotRows[$key] = [
                    'person_id' => $person->id,
                    'job' => 'Actor',
                    'character' => $member['character'],
                    'department' => 'Acting',
                    'cast_order' => $member['order'],
                ];
            }
            $castWithSlug[] = array_merge($member, ['slug' => $person->slug]);
        }

        foreach ($dto->crew as $member) {
            $person = Person::firstOrCreate(
                ['tmdb_id' => $member['tmdb_id']],
                [
                    'name' => $member['name'],
                    'profile_path' => $member['profile_path'],
                    'slug' => Str::slug($member['name']).'-'.$member['tmdb_id'],
                ]
            );
            $key = $person->id.'|'.$member['job'].'|';
            if (! isset($pivotRows[$key])) {
                $pivotRows[$key] = [
                    'person_id' => $person->id,
                    'job' => $member['job'],
                    'character' => null,
                    'department' => $member['department'],
                    'cast_order' => null,
                ];
            }
            $crewWithSlug[] = array_merge($member, ['slug' => $person->slug]);
        }

        $movie->people()->detach();
        foreach ($pivotRows as $row) {
            $personId = $row['person_id'];
            unset($row['person_id']);
            $movie->people()->attach($personId, $row);
        }

        return [
            'cast' => $castWithSlug,
            'crew' => $crewWithSlug,
        ];
    }
}
