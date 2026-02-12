<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NewsSourceSeeder extends Seeder
{
    /**
     * Seed entertainment/film news sources. Uses the North Cloud package NewsSource model.
     * Sources are firstOrCreate by slug so the list can be re-run or extended.
     */
    public function run(): void
    {
        $modelClass = config('northcloud.models.news_source');
        $sources = config('entertainment-sources', []);

        foreach ($sources as $source) {
            $slug = $source['slug'] ?? Str::slug($source['name']);
            $modelClass::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $source['name'],
                    'url' => $source['url'],
                    'is_active' => true,
                ]
            );
        }
    }
}
