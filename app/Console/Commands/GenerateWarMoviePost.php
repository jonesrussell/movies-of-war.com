<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Movie;
use Illuminate\Console\Command;
use JonesRussell\XSuite\Models\XPost;

class GenerateWarMoviePost extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'x:generate-content';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-generate "War Movie of the Day" post from movie database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Select a random published movie
        $movie = Movie::published()->inRandomOrder()->first();

        if (! $movie) {
            $this->error('No published movies found.');

            return Command::FAILURE;
        }

        // Generate tweet content
        $content = $this->generateTweetContent($movie);

        // Create X post as draft
        $xPost = XPost::create([
            'content' => $content,
            'media_urls' => $movie->poster_path ? [$movie->poster_path] : null,
            'status' => 'draft',
            'user_id' => 1, // Admin user ID (adjust as needed)
        ]);

        $this->info("Generated post for: {$movie->title}");
        $this->info("Post ID: {$xPost->id} (status: draft)");

        return Command::SUCCESS;
    }

    /**
     * Generate tweet content for a movie.
     */
    protected function generateTweetContent(Movie $movie): string
    {
        $templates = [
            "🎬 War Movie of the Day: {$movie->title} ({$movie->release_year})\n\n{$movie->synopsis}\n\n#WarMovies #{$movie->release_year}",
            "⚔️ {$movie->title} ({$movie->release_year})\n\nA powerful war film worth watching.\n\n#WarMovies",
            "🎥 {$movie->title}\n\nReleased in {$movie->release_year}, this war film explores themes of conflict and humanity.\n\n#WarMovies #ClassicWarFilms",
        ];

        // Select random template
        $template = $templates[array_rand($templates)];

        // Truncate to 280 characters if needed
        $maxLength = config('x-suite.max_tweet_length', 280);
        if (strlen($template) > $maxLength) {
            $template = substr($template, 0, $maxLength - 3).'...';
        }

        return $template;
    }
}
