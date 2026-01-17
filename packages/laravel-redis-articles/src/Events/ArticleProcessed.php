<?php

namespace JonesRussell\LaravelRedisArticles\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JonesRussell\LaravelRedisArticles\Contracts\ArticleModel;

class ArticleProcessed
{
    use Dispatchable, SerializesModels;

    public function __construct(public ArticleModel $article) {}
}
