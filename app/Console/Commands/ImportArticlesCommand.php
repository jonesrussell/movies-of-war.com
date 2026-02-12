<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\WarArticle;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportArticlesCommand extends Command
{
    protected $signature = 'articles:import
                            {file : Path to JSON or CSV file}
                            {--format= : json or csv (default: infer from extension)}';

    protected $description = 'Import articles from a JSON or CSV file (backfill); creates NewsSource and WarArticle records';

    public function handle(): int
    {
        $path = $this->argument('file');
        $format = $this->option('format') ?? $this->inferFormat($path);

        if (! is_file($path) || ! is_readable($path)) {
            $this->error("File not found or not readable: {$path}");

            return self::FAILURE;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            $this->error("Could not read file: {$path}");

            return self::FAILURE;
        }

        $rows = $format === 'csv' ? $this->parseCsv($content) : $this->parseJson($content);
        if ($rows === []) {
            $this->warn('No rows to import.');

            return self::SUCCESS;
        }

        $newsSourceModel = config('northcloud.models.news_source');
        $tagModel = config('northcloud.models.tag');
        $imported = 0;
        $skipped = 0;

        foreach ($rows as $i => $row) {
            $title = $this->getRowValue($row, 'title');
            $url = $this->getRowValue($row, 'url');
            if (! $title || ! $url) {
                $this->warn('Row '.($i + 1).': missing title or url, skipping.');
                $skipped++;

                continue;
            }

            $sourceName = $this->getRowValue($row, 'source_name') ?: $this->getRowValue($row, 'source_url') ?: 'Unknown';
            $sourceUrl = $this->getRowValue($row, 'source_url') ?: $url;
            $slug = Str::slug($title).'-'.uniqid();
            if (WarArticle::query()->where('url', $url)->exists() || WarArticle::query()->where('slug', $slug)->exists()) {
                $skipped++;

                continue;
            }

            $sourceSlug = Str::slug(parse_url($sourceUrl, PHP_URL_HOST) ?: $sourceName);
            $source = $newsSourceModel::firstOrCreate(
                ['slug' => $sourceSlug],
                [
                    'name' => $sourceName,
                    'url' => $sourceUrl,
                    'is_active' => true,
                ]
            );

            $publishedAt = $this->getRowValue($row, 'published_at');
            $publishedAt = $publishedAt ? (is_numeric($publishedAt) ? date('Y-m-d H:i:s', (int) $publishedAt) : $publishedAt) : now();

            $article = WarArticle::create([
                'news_source_id' => $source->id,
                'title' => $title,
                'slug' => $slug,
                'excerpt' => $this->getRowValue($row, 'excerpt'),
                'content' => $this->getRowValue($row, 'content'),
                'url' => $url,
                'external_id' => $this->getRowValue($row, 'external_id') ?: 'import-'.uniqid(),
                'image_url' => $this->getRowValue($row, 'image_url'),
                'author' => $this->getRowValue($row, 'author'),
                'status' => 'published',
                'published_at' => $publishedAt,
                'crawled_at' => now(),
                'metadata' => [],
                'war_era' => $this->getRowValue($row, 'war_era'),
            ]);

            $topicSlugs = $this->getRowValue($row, 'topics');
            if ($topicSlugs !== null && $topicSlugs !== '') {
                $slugs = is_array($topicSlugs) ? $topicSlugs : array_map('trim', explode(',', (string) $topicSlugs));
                $tagIds = $tagModel::query()->whereIn('slug', $slugs)->pluck('id')->toArray();
                if ($tagIds !== []) {
                    $article->tags()->sync(array_fill_keys($tagIds, ['confidence' => 0.8]));
                }
            }

            $imported++;
        }

        $this->info("Imported {$imported} articles, skipped {$skipped}.");

        return self::SUCCESS;
    }

    private function inferFormat(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'csv') {
            return 'csv';
        }
        if (in_array($ext, ['json'], true)) {
            return 'json';
        }
        $this->warn('Could not infer format from extension; defaulting to json.');

        return 'json';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseJson(string $content): array
    {
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON: '.json_last_error_msg());

            return [];
        }
        if (! is_array($data)) {
            return [];
        }

        return isset($data[0]) && is_array($data[0]) ? $data : [$data];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseCsv(string $content): array
    {
        $lines = array_filter(explode("\n", $content), fn (string $line) => trim($line) !== '');
        if ($lines === []) {
            return [];
        }
        $header = str_getcsv(array_shift($lines));
        $rows = [];
        foreach ($lines as $line) {
            $values = str_getcsv($line);
            $padded = array_slice(array_pad($values, count($header), null), 0, count($header));
            $rows[] = array_combine($header, $padded) ?: [];
        }

        return $rows;
    }

    private function getRowValue(array $row, string $key): mixed
    {
        $value = $row[$key] ?? null;
        if ($value === '' || $value === null) {
            return null;
        }

        return $value;
    }
}
