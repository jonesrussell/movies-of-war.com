<?php

namespace JonesRussell\LaravelRedisArticles\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessIncomingArticle implements ShouldQueue
{
    use Queueable;

    public function __construct(public array $articleData) {}

    public function handle(): void
    {
        try {
            $processorClass = config('redis-articles.processing.processor');
            $articleModelClass = config('redis-articles.models.article');
            $newsSourceModelClass = config('redis-articles.models.news_source');
            $tagModelClass = config('redis-articles.models.tag');

            $processor = new $processorClass(
                $articleModelClass,
                $newsSourceModelClass,
                $tagModelClass
            );

            $processor->process($this->articleData);
        } catch (\Exception $e) {
            Log::error('ProcessIncomingArticle job failed', [
                'error' => $e->getMessage(),
                'external_id' => $this->articleData['id'] ?? null,
            ]);

            throw $e;
        }
    }
}
