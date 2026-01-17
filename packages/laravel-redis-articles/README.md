# Laravel Redis Articles

A Laravel package for ingesting articles from Redis pub/sub channels with automatic deduplication, source tracking, and tag management.

## Features

- ✅ Redis pub/sub article ingestion
- ✅ Automatic deduplication by external_id
- ✅ News source extraction and tracking
- ✅ Tag creation and attachment
- ✅ Content sanitization (XSS prevention)
- ✅ Quality score filtering
- ✅ Metadata storage (JSON)
- ✅ Event-driven architecture
- ✅ Status workflow (draft → published → archived)
- ✅ Extensible via contracts and events

## Requirements

- PHP 8.4+
- Laravel 12+
- Redis with pub/sub support

## Installation

### 1. Install via Composer (Local Path Repository)

Add the package repository to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../packages/laravel-redis-articles"
        }
    ]
}
```

Then require the package:

```bash
composer require jonesrussell/laravel-redis-articles:@dev
```

### 2. Publish Configuration and Migrations

```bash
php artisan vendor:publish --tag=redis-articles-config
php artisan vendor:publish --tag=redis-articles-migrations
```

### 3. Run Migrations

```bash
php artisan migrate
```

## Configuration

Edit `config/redis-articles.php` to customize:

```php
return [
    'redis' => [
        'connection' => 'default',
        'channel' => 'articles:default',
    ],
    'quality' => [
        'min_score' => 70,
        'enabled' => true,
    ],
    'models' => [
        'article' => \App\Models\YourArticle::class,
        'news_source' => \JonesRussell\LaravelRedisArticles\Models\NewsSource::class,
        'tag' => \JonesRussell\LaravelRedisArticles\Models\Tag::class,
    ],
    // ... more options
];
```

## Usage

### Creating Your Article Model

Extend the package's abstract Article model:

```php
<?php

namespace App\Models;

use JonesRussell\LaravelRedisArticles\Models\Article as BaseArticle;

class WarArticle extends BaseArticle
{
    protected $table = 'articles';

    protected $fillable = [
        ...parent::$fillable,
        'war_era', // Your custom field
    ];

    // Add your custom relationships
    public function movies()
    {
        return $this->belongsToMany(Movie::class, 'article_movie');
    }
}
```

Update your config to use this model:

```php
'models' => [
    'article' => \App\Models\WarArticle::class,
],
```

### Subscribe to Redis Channel

Start the subscriber daemon:

```bash
php artisan articles:subscribe articles:war
```

The command will listen for messages on the specified Redis channel and process them automatically.

### Message Format

Publish JSON messages to your Redis channel:

```json
{
  "id": "external-article-id-123",
  "title": "Breaking News Article",
  "body": "Full article content...",
  "url": "https://example.com/article",
  "og_image": "https://example.com/image.jpg",
  "author": "John Doe",
  "published_date": "2026-01-16T08:00:00Z",
  "quality_score": 85,
  "topics": ["war", "history", "wwii"],
  "publisher": {
    "route_id": "uuid-here",
    "published_at": "2026-01-16T08:00:00Z",
    "channel": "articles:war"
  }
}
```

### Event Listeners

Hook into the article lifecycle with events:

```php
use JonesRussell\LaravelRedisArticles\Events\ArticleProcessed;

class LinkArticlesToMovies
{
    public function handle(ArticleProcessed $event): void
    {
        $article = $event->article;

        // Your custom logic to link articles to movies
        $movies = Movie::whereHas('tags', function ($query) use ($article) {
            $query->whereIn('slug', $article->tags->pluck('slug'));
        })->get();

        $article->movies()->sync($movies->pluck('id'));
    }
}
```

Register in `EventServiceProvider`:

```php
protected $listen = [
    ArticleProcessed::class => [
        LinkArticlesToMovies::class,
    ],
];
```

## DDEV Setup

Add to `.ddev/config.yaml`:

```yaml
web_extra_daemons:
  - name: articles-subscribe
    command: "php /var/www/html/artisan articles:subscribe articles:war"
    directory: /var/www/html
```

Check status:

```bash
ddev supervisor status
ddev supervisor tail articles-subscribe
```

## Production Deployment

### Using Supervisor

Create `/etc/supervisor/conf.d/articles-subscribe.conf`:

```ini
[program:articles-subscribe]
command=php /path/to/artisan articles:subscribe articles:war
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/articles-subscribe.log
```

Reload supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start articles-subscribe
```

## Testing

Publish a test message to Redis:

```bash
redis-cli PUBLISH articles:war '{
  "id": "test-123",
  "title": "Test Article",
  "body": "Test content",
  "publisher": {"channel": "articles:war"}
}'
```

Verify the article was created:

```bash
php artisan tinker
>>> App\Models\WarArticle::where('external_id', 'test-123')->first()
```

## Events

The package dispatches three events:

1. **ArticleReceived** - Fired when a message is received from Redis
2. **ArticleProcessed** - Fired after an article is saved to the database
3. **ArticlePublished** - Fired when an article status changes to published

## API

### ArticleModel Contract

Your article model must implement:

- `getExternalId(): string`
- `getTitle(): string`
- `getUrl(): ?string`
- `getStatus(): string`
- `isPublished(): bool`

### Article Status Methods

- `isDraft(): bool`
- `isPublished(): bool`
- `isArchived(): bool`
- `markAsPublished(): void`
- `markAsArchived(): void`

### Scopes

- `draft()` - Get draft articles
- `published()` - Get published articles
- `archived()` - Get archived articles
- `featured()` - Get featured articles
- `withTag(string $slug)` - Filter by tag
- `search(string $term)` - Search articles

## License

MIT License - see LICENSE file for details.
