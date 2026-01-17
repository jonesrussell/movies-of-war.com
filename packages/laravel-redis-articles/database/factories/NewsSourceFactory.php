<?php

namespace JonesRussell\LaravelRedisArticles\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use JonesRussell\LaravelRedisArticles\Models\NewsSource;

class NewsSourceFactory extends Factory
{
    protected $model = NewsSource::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'url' => fake()->url(),
            'logo_url' => null,
            'description' => fake()->paragraph(),
            'credibility_score' => fake()->numberBetween(40, 95),
            'bias_rating' => fake()->randomElement(['left', 'center-left', 'center', 'center-right', 'right']),
            'factual_reporting_score' => fake()->numberBetween(50, 100),
            'ownership' => fake()->company(),
            'country' => fake()->countryCode(),
            'is_active' => true,
            'metadata' => [],
        ];
    }
}
