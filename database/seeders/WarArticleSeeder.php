<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tag;
use App\Models\WarArticle;
use Illuminate\Database\Seeder;

class WarArticleSeeder extends Seeder
{
    /**
     * Seed sample published articles for local UI development.
     * Run NewsSourceSeeder first so news_sources exist.
     */
    public function run(): void
    {
        $tagIds = Tag::query()->whereIn('slug', ['wwii', 'drama', 'historical', 'action'])->pluck('id')->toArray();

        WarArticle::factory()
            ->count(8)
            ->published()
            ->create()
            ->each(function (WarArticle $article) use ($tagIds): void {
                $this->attachRandomTags($article, $tagIds, 1, 3);
            });

        WarArticle::factory()
            ->count(2)
            ->published()
            ->withWarEra('WWII')
            ->create()
            ->each(function (WarArticle $article) use ($tagIds): void {
                $this->attachRandomTags($article, $tagIds, 1, 2);
            });
    }

    private function attachRandomTags(WarArticle $article, array $tagIds, int $min, int $max): void
    {
        if ($tagIds === []) {
            return;
        }
        $shuffled = $tagIds;
        shuffle($shuffled);
        $take = min($max, max($min, rand($min, $max)), count($shuffled));
        $selected = array_slice($shuffled, 0, $take);
        $article->tags()->sync(
            array_fill_keys($selected, ['confidence' => round(rand(70, 100) / 100.0, 2)])
        );
    }
}
