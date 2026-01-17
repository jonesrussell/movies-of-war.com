<?php

namespace JonesRussell\LaravelRedisArticles\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use JonesRussell\LaravelRedisArticles\Models\Tag;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'type' => config('redis-articles.tags.default_type', 'article_category'),
            'color' => fake()->randomElement(['red', 'blue', 'green', 'yellow', 'purple', 'orange', 'gray']),
            'description' => fake()->sentence(),
            'article_count' => 0,
        ];
    }

    public function type(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }
}
