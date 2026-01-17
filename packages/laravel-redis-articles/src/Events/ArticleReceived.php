<?php

namespace JonesRussell\LaravelRedisArticles\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ArticleReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public array $articleData,
        public string $channel
    ) {}
}
