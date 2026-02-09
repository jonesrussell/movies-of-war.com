<?php

namespace JonesRussell\NorthcloudLaravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JonesRussell\NorthcloudLaravel\Contracts\ArticleModel;

class ArticleProcessed
{
    use Dispatchable, SerializesModels;

    public function __construct(public ArticleModel $article) {}
}
