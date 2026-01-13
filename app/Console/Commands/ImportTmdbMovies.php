<?php

namespace App\Console\Commands;

use App\Models\Movie;
use App\Models\Tag;
use App\Services\TMDBService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportTmdbMovies extends Command
{
    protected $signature = 'tmdb:import {--limit=30 : Number of movies to import} {--download-posters : Download poster images}';

    protected $description = 'Import war movies from The Movie Database (TMDB)';

    protected TMDBService $tmdb;

    public function handle(): int
    {
        if (! config('tmdb.api_key')) {
            $this->error('TMDB API key not configured. Please set TMDB_API_KEY in your .env file.');
            $this->info('Get your free API key at: https://www.themoviedb.org/settings/api');

            return self::FAILURE;
        }

        $this->tmdb = app(TMDBService::class);
        $limit = (int) $this->option('limit');
        $downloadPosters = $this->option('download-posters');

        $this->info("Fetching war movies from TMDB (limit: {$limit})...");

        $warMovieIds = $this->getWarMovieIds($limit);

        $this->info("Found {$warMovieIds->count()} movies. Importing...");

        $progressBar = $this->output->createProgressBar($warMovieIds->count());
        $imported = 0;

        foreach ($warMovieIds as $tmdbId) {
            try {
                $details = $this->tmdb->getMovieDetails($tmdbId);

                if ($details) {
                    $this->importMovie($details, $downloadPosters);
                    $imported++;
                }

                $progressBar->advance();
                usleep(250000); // Rate limiting: 4 requests per second
            } catch (\Exception $e) {
                $this->warn("\nError importing movie ID {$tmdbId}: {$e->getMessage()}");
            }
        }

        $progressBar->finish();
        $this->newLine(2);
        $this->info("Successfully imported {$imported} movies!");

        return self::SUCCESS;
    }

    protected function getWarMovieIds(int $limit): \Illuminate\Support\Collection
    {
        $ids = collect();
        $page = 1;

        while ($ids->count() < $limit) {
            $movies = $this->tmdb->discoverWarMovies($page);

            if (empty($movies)) {
                break;
            }

            foreach ($movies as $movie) {
                $ids->push($movie['id']);

                if ($ids->count() >= $limit) {
                    break;
                }
            }

            $page++;
        }

        return $ids;
    }

    protected function importMovie(array $details, bool $downloadPosters): void
    {
        $posterPath = null;
        $posterUrl = null;

        if ($details['poster_path']) {
            if ($downloadPosters) {
                $posterPath = $this->tmdb->downloadPoster($details['poster_path']);
            }
            $posterUrl = $this->tmdb->getPosterUrl($details['poster_path']);
        }

        $trailerUrl = $this->tmdb->getYoutubeTrailerUrl($details['videos'] ?? null);

        $movie = Movie::firstOrNew(['tmdb_id' => $details['id']]);

        $movie->fill([
            'title' => $details['title'],
            'slug' => Str::slug($details['title']),
            'release_year' => (int) substr($details['release_date'] ?? now()->year, 0, 4),
            'release_date' => $details['release_date'] ?? null,
            'synopsis' => $details['overview'] ?? '',
            'runtime' => $details['runtime'] ?? null,
            'poster_path' => $posterPath,
            'poster_url' => $posterUrl,
            'trailer_url' => $trailerUrl,
            'imdb_id' => $details['imdb_id'] ?? null,
            'is_upcoming' => ! empty($details['release_date']) && $details['release_date'] > now(),
        ]);

        // Only set status to draft if this is a new movie
        if (! $movie->exists) {
            $movie->status = Movie::STATUS_DRAFT;
        }

        $movie->save();

        // Tag the movie
        $this->tagMovie($movie, $details);
    }

    protected function tagMovie(Movie $movie, array $details): void
    {
        $tags = collect();

        // Add genre tags
        foreach ($details['genres'] ?? [] as $genre) {
            $tag = Tag::firstOrCreate(
                ['slug' => Str::slug($genre['name'])],
                ['name' => $genre['name'], 'type' => 'genre']
            );
            $tags->push($tag->id);
        }

        // Add era tags based on keywords
        if (isset($details['keywords']['keywords'])) {
            foreach ($details['keywords']['keywords'] as $keyword) {
                $keywordName = $keyword['name'];

                if (str_contains($keywordName, 'world war ii') || str_contains($keywordName, 'ww2')) {
                    $tag = Tag::firstOrCreate(['slug' => 'wwii'], ['name' => 'WWII', 'type' => 'era']);
                    $tags->push($tag->id);
                } elseif (str_contains($keywordName, 'world war i') || str_contains($keywordName, 'ww1')) {
                    $tag = Tag::firstOrCreate(['slug' => 'wwi'], ['name' => 'WWI', 'type' => 'era']);
                    $tags->push($tag->id);
                } elseif (str_contains($keywordName, 'vietnam')) {
                    $tag = Tag::firstOrCreate(['slug' => 'vietnam-war'], ['name' => 'Vietnam War', 'type' => 'era']);
                    $tags->push($tag->id);
                }
            }
        }

        $movie->tags()->sync($tags->unique());
    }
}
