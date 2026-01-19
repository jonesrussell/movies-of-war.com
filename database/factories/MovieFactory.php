<?php

namespace Database\Factories;

use App\Enums\MovieStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Movie>
 */
class MovieFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(rand(2, 4));
        $releaseYear = fake()->numberBetween(1940, 2025);

        return [
            'title' => rtrim($title, '.'),
            'slug' => Str::slug($title),
            'release_year' => $releaseYear,
            'release_date' => fake()->dateTimeBetween("{$releaseYear}-01-01", "{$releaseYear}-12-31"),
            'synopsis' => fake()->paragraphs(3, true),
            'runtime' => fake()->numberBetween(90, 180),
            'country' => fake()->randomElement(['USA', 'UK', 'Germany', 'France', 'Russia', 'Japan', 'Italy']),
            'conflict' => fake()->randomElement(['WWI', 'WWII', 'Vietnam War', 'Korean War', 'Gulf War', 'Afghanistan War', 'Iraq War']),
            'poster_path' => null,
            'poster_url' => fake()->imageUrl(300, 450, 'movie', true),
            'trailer_url' => fake()->url(),
            'imdb_id' => 'tt'.fake()->numerify('#######'),
            'is_upcoming' => false,
        ];
    }

    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_upcoming' => true,
            'release_date' => fake()->dateTimeBetween('now', '+1 year'),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MovieStatus::Draft,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MovieStatus::Published,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MovieStatus::Archived,
        ]);
    }
}
