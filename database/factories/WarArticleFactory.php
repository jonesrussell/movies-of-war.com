<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\WarArticle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WarArticle>
 */
class WarArticleFactory extends Factory
{
    protected $model = WarArticle::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(rand(3, 6));
        $slug = Str::slug($title).'-'.fake()->unique()->numerify('#####');
        $url = 'https://example.com/'.Str::slug($title).'-'.fake()->unique()->numerify('#####');

        $newsSourceModel = config('northcloud.models.news_source');
        $newsSourceId = $newsSourceModel::query()->first()?->id
            ?? $newsSourceModel::factory()->create()->id;

        return [
            'news_source_id' => $newsSourceId,
            'title' => rtrim($title, '.'),
            'slug' => $slug,
            'excerpt' => fake()->paragraph(),
            'content' => fake()->paragraphs(4, true),
            'url' => $url,
            'external_id' => 'ext-'.fake()->unique()->uuid(),
            'image_url' => fake()->imageUrl(800, 450, 'movies', true),
            'author' => fake()->name(),
            'status' => 'draft',
            'published_at' => null,
            'crawled_at' => now(),
            'metadata' => [],
            'view_count' => 0,
            'is_featured' => false,
            'war_era' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => $attributes['published_at'] ?? now()->subDays(rand(1, 90)),
        ]);
    }

    public function withWarEra(string $era): static
    {
        return $this->state(fn (array $attributes) => [
            'war_era' => $era,
        ]);
    }
}
