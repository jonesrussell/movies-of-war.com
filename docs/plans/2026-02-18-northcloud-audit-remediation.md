# NorthCloud Audit Remediation Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Bring northcloud-laravel to 0.7.0 and movies-of-war.com to 96%+ NorthCloud Platform Standard compliance.

**Architecture:** Package-first sequential — all northcloud-laravel 0.7.0 changes complete and tagged before movies-of-war.com consumes them. The package adds config validation, navigation API, factory autoloading fix, linking config, and strict types. The consumer app then fixes namespace bugs, config schema, middleware, type safety, and strict types.

**Tech Stack:** PHP 8.4, Laravel 12, Pest v4, Orchestra Testbench, Vue 3, Inertia v2

---

## Phase 1: northcloud-laravel 0.7.0

All work in `/home/jones/dev/northcloud-laravel`. Tests run with `vendor/bin/pest`.

---

### Task 1: Add Config Schema Validation

**Files:**
- Create: `src/Support/ConfigValidator.php`
- Modify: `src/NorthCloudServiceProvider.php:46-65`
- Create: `src/Console/Commands/NorthCloudDoctor.php`
- Test: `tests/Feature/ConfigValidationTest.php`
- Test: `tests/Feature/NorthCloudDoctorCommandTest.php`

**Step 1: Write the failing test for config validation**

```php
<?php
// tests/Feature/ConfigValidationTest.php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;

it('warns when singular channel key is used', function () {
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn (string $msg) => str_contains($msg, 'northcloud.redis.channel'));

    config()->set('northcloud.redis.channel', 'articles:test');
    config()->offsetUnset('northcloud.redis.channels');

    app(\JonesRussell\NorthCloud\Support\ConfigValidator::class)->validate();
});

it('warns when singular processor key is used', function () {
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn (string $msg) => str_contains($msg, 'northcloud.processing.processor'));

    config()->set('northcloud.processing.processor', 'SomeClass');

    app(\JonesRussell\NorthCloud\Support\ConfigValidator::class)->validate();
});

it('warns about unknown config keys', function () {
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn (string $msg) => str_contains($msg, 'content.max_excerpt_length'));

    config()->set('northcloud.content.max_excerpt_length', 500);

    app(\JonesRussell\NorthCloud\Support\ConfigValidator::class)->validate();
});

it('does not warn when config is correct', function () {
    Log::shouldReceive('warning')->never();

    app(\JonesRussell\NorthCloud\Support\ConfigValidator::class)->validate();
});
```

**Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Feature/ConfigValidationTest.php`
Expected: FAIL — class `ConfigValidator` does not exist

**Step 3: Implement ConfigValidator**

```php
<?php
// src/Support/ConfigValidator.php

declare(strict_types=1);

namespace JonesRussell\NorthCloud\Support;

use Illuminate\Support\Facades\Log;

class ConfigValidator
{
    /** @var list<string> */
    private const KNOWN_TOP_KEYS = [
        'migrations', 'redis', 'quality', 'models', 'processors', 'processing',
        'content', 'tags', 'navigation', 'articleable', 'users', 'admin', 'mail', 'linking',
    ];

    /** @var array<string, list<string>> */
    private const KNOWN_NESTED_KEYS = [
        'redis' => ['connection', 'channels'],
        'content' => ['allowed_tags'],
        'processing' => ['sync'],
    ];

    public function validate(): void
    {
        $this->checkDeprecatedKeys();
        $this->checkUnknownKeys();
    }

    private function checkDeprecatedKeys(): void
    {
        if (config()->has('northcloud.redis.channel')) {
            Log::warning(
                'NorthCloud config: "northcloud.redis.channel" (singular) is deprecated. '
                .'Use "northcloud.redis.channels" (array) instead.'
            );
        }

        if (config()->has('northcloud.processing.processor')) {
            Log::warning(
                'NorthCloud config: "northcloud.processing.processor" (singular) is deprecated. '
                .'Use the root-level "northcloud.processors" (array) instead.'
            );
        }
    }

    private function checkUnknownKeys(): void
    {
        $config = config('northcloud', []);

        foreach ($config as $key => $value) {
            if (! in_array($key, self::KNOWN_TOP_KEYS, true)) {
                Log::warning("NorthCloud config: unknown top-level key \"{$key}\".");
            }
        }

        foreach (self::KNOWN_NESTED_KEYS as $section => $allowedKeys) {
            $sectionConfig = config("northcloud.{$section}", []);
            if (! is_array($sectionConfig)) {
                continue;
            }
            foreach (array_keys($sectionConfig) as $nestedKey) {
                if (! in_array($nestedKey, $allowedKeys, true)) {
                    Log::warning("NorthCloud config: unknown key \"{$section}.{$nestedKey}\".");
                }
            }
        }
    }
}
```

Register in service provider — add to `boot()` after line 59:

```php
app(Support\ConfigValidator::class)->validate();
```

**Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Feature/ConfigValidationTest.php`
Expected: PASS (4 tests)

**Step 5: Write and run the northcloud:doctor command test**

```php
<?php
// tests/Feature/NorthCloudDoctorCommandTest.php

declare(strict_types=1);

it('reports all clear when config is valid', function () {
    $this->artisan('northcloud:doctor')
        ->expectsOutputToContain('All checks passed')
        ->assertSuccessful();
});

it('reports deprecated channel key', function () {
    config()->set('northcloud.redis.channel', 'test');
    config()->offsetUnset('northcloud.redis.channels');

    $this->artisan('northcloud:doctor')
        ->expectsOutputToContain('redis.channel')
        ->assertFailed();
});
```

**Step 6: Implement the doctor command**

```php
<?php
// src/Console/Commands/NorthCloudDoctor.php

declare(strict_types=1);

namespace JonesRussell\NorthCloud\Console\Commands;

use Illuminate\Console\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class NorthCloudDoctor extends Command
{
    protected $signature = 'northcloud:doctor';

    protected $description = 'Validate NorthCloud configuration';

    public function handle(): int
    {
        $errors = [];

        if (config()->has('northcloud.redis.channel')) {
            $errors[] = '"northcloud.redis.channel" (singular) should be "northcloud.redis.channels" (array).';
        }

        if (config()->has('northcloud.processing.processor')) {
            $errors[] = '"northcloud.processing.processor" should be root-level "northcloud.processors" (array).';
        }

        $knownTopKeys = [
            'migrations', 'redis', 'quality', 'models', 'processors', 'processing',
            'content', 'tags', 'navigation', 'articleable', 'users', 'admin', 'mail', 'linking',
        ];

        foreach (array_keys(config('northcloud', [])) as $key) {
            if (! in_array($key, $knownTopKeys, true)) {
                $errors[] = "Unknown top-level config key: \"{$key}\".";
            }
        }

        $knownNested = ['redis' => ['connection', 'channels'], 'content' => ['allowed_tags'], 'processing' => ['sync']];
        foreach ($knownNested as $section => $allowed) {
            foreach (array_keys(config("northcloud.{$section}", [])) as $nestedKey) {
                if (! in_array($nestedKey, $allowed, true)) {
                    $errors[] = "Unknown config key: \"{$section}.{$nestedKey}\".";
                }
            }
        }

        if ($errors === []) {
            info('All checks passed. NorthCloud configuration is valid.');

            return self::SUCCESS;
        }

        foreach ($errors as $err) {
            error($err);
        }

        return self::FAILURE;
    }
}
```

Register in `NorthCloudServiceProvider::registerCommands()` — add `Console\Commands\NorthCloudDoctor::class` to the commands array.

**Step 7: Run all tests and verify**

Run: `vendor/bin/pest tests/Feature/ConfigValidationTest.php tests/Feature/NorthCloudDoctorCommandTest.php`
Expected: PASS (6 tests)

**Step 8: Commit**

```bash
git add src/Support/ConfigValidator.php src/Console/Commands/NorthCloudDoctor.php \
  src/NorthCloudServiceProvider.php \
  tests/Feature/ConfigValidationTest.php tests/Feature/NorthCloudDoctorCommandTest.php
git commit -m "feat: add config schema validation and northcloud:doctor command"
```

---

### Task 2: Add Navigation Registration API

**Files:**
- Create: `src/NorthCloud.php`
- Create: `src/Facades/NorthCloud.php`
- Modify: `src/NorthCloudServiceProvider.php:16-17,44,59,107-126`
- Test: `tests/Feature/NavigationTest.php`

**Step 1: Write the failing test**

```php
<?php
// tests/Feature/NavigationTest.php

declare(strict_types=1);

use JonesRussell\NorthCloud\NorthCloud;

it('shares config-based navigation items via Inertia', function () {
    config()->set('northcloud.navigation.items', [
        ['title' => 'Articles', 'route' => 'dashboard.articles.index', 'icon' => 'FileText'],
    ]);

    \Illuminate\Support\Facades\Route::get('/dashboard/articles', fn () => 'ok')
        ->name('dashboard.articles.index')
        ->middleware('web');

    $shared = app('inertia')->getShared();
    $northcloud = value($shared['northcloud']);

    expect($northcloud['navigation'])->toHaveCount(1);
    expect($northcloud['navigation'][0]['title'])->toBe('Articles');
});

it('merges programmatically registered navigation items', function () {
    config()->set('northcloud.navigation.items', [
        ['title' => 'Articles', 'route' => 'dashboard.articles.index', 'icon' => 'FileText'],
    ]);

    \Illuminate\Support\Facades\Route::get('/dashboard/articles', fn () => 'ok')
        ->name('dashboard.articles.index')
        ->middleware('web');
    \Illuminate\Support\Facades\Route::get('/dashboard/movies', fn () => 'ok')
        ->name('dashboard.movies')
        ->middleware('web');

    NorthCloud::registerNavigation([
        ['title' => 'Movies', 'route' => 'dashboard.movies', 'icon' => 'Film'],
    ]);

    $shared = app('inertia')->getShared();
    $northcloud = value($shared['northcloud']);

    expect($northcloud['navigation'])->toHaveCount(2);
    expect($northcloud['navigation'][1]['title'])->toBe('Movies');
});
```

**Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Feature/NavigationTest.php`
Expected: FAIL — class `NorthCloud` does not exist

**Step 3: Implement the NorthCloud manager class**

```php
<?php
// src/NorthCloud.php

declare(strict_types=1);

namespace JonesRussell\NorthCloud;

class NorthCloud
{
    /** @var list<array{title: string, route: string, icon: string}> */
    private array $registeredNavItems = [];

    /**
     * @param  list<array{title: string, route: string, icon: string}>  $items
     */
    public function registerNavigation(array $items): void
    {
        foreach ($items as $item) {
            $this->registeredNavItems[] = $item;
        }
    }

    /**
     * @return list<array{title: string, route: string, icon: string}>
     */
    public function getRegisteredNavigation(): array
    {
        return $this->registeredNavItems;
    }
}
```

```php
<?php
// src/Facades/NorthCloud.php

declare(strict_types=1);

namespace JonesRussell\NorthCloud\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void registerNavigation(array $items)
 * @method static array getRegisteredNavigation()
 */
class NorthCloud extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \JonesRussell\NorthCloud\NorthCloud::class;
    }
}
```

**Step 4: Register the singleton and update shareNavigation in service provider**

In `NorthCloudServiceProvider::register()`, add at line 17:

```php
$this->app->singleton(\JonesRussell\NorthCloud\NorthCloud::class);
```

Update `shareNavigation()` to merge programmatic items:

```php
protected function shareNavigation(): void
{
    if (! config('northcloud.navigation.enabled', true)) {
        return;
    }

    if (! class_exists(Inertia::class)) {
        return;
    }

    Inertia::share('northcloud', fn () => [
        'navigation' => collect(config('northcloud.navigation.items', []))
            ->merge(app(NorthCloud::class)->getRegisteredNavigation())
            ->map(fn (array $item) => [
                'title' => $item['title'],
                'href' => route($item['route']),
                'icon' => $item['icon'],
            ])
            ->all(),
    ]);
}
```

Add facade alias to `composer.json` `extra.laravel`:

```json
"aliases": {
    "NorthCloud": "JonesRussell\\NorthCloud\\Facades\\NorthCloud"
}
```

**Step 5: Run test to verify it passes**

Run: `vendor/bin/pest tests/Feature/NavigationTest.php`
Expected: PASS (2 tests)

**Step 6: Commit**

```bash
git add src/NorthCloud.php src/Facades/NorthCloud.php \
  src/NorthCloudServiceProvider.php composer.json \
  tests/Feature/NavigationTest.php
git commit -m "feat: add NorthCloud::registerNavigation() programmatic API"
```

---

### Task 3: Fix Factory Autoloading

**Files:**
- Modify: `composer.json:25-28` (move factory autoload from `autoload-dev` to `autoload`)
- Test: `tests/Unit/Models/FactoryResolutionTest.php`

**Step 1: Write the failing test**

```php
<?php
// tests/Unit/Models/FactoryResolutionTest.php

declare(strict_types=1);

use JonesRussell\NorthCloud\Models\Article;
use JonesRussell\NorthCloud\Models\NewsSource;
use JonesRussell\NorthCloud\Models\Tag;

it('resolves Article factory', function () {
    $article = Article::factory()->make();
    expect($article)->toBeInstanceOf(Article::class);
    expect($article->title)->toBeString();
});

it('resolves NewsSource factory', function () {
    $source = NewsSource::factory()->make();
    expect($source)->toBeInstanceOf(NewsSource::class);
    expect($source->name)->toBeString();
});

it('resolves Tag factory', function () {
    $tag = Tag::factory()->make();
    expect($tag)->toBeInstanceOf(Tag::class);
    expect($tag->name)->toBeString();
});
```

**Step 2: Run test to verify current behavior**

Run: `vendor/bin/pest tests/Unit/Models/FactoryResolutionTest.php`
Expected: These may pass locally (autoload-dev is loaded in dev) but will fail in consumer apps. Verify they pass here, then proceed.

**Step 3: Move factory autoload to production autoload**

In `composer.json`, move `"JonesRussell\\NorthCloud\\Database\\Factories\\": "database/factories/"` from `autoload-dev.psr-4` to `autoload.psr-4`:

```json
"autoload": {
    "psr-4": {
        "JonesRussell\\NorthCloud\\": "src/",
        "JonesRussell\\NorthCloud\\Database\\Factories\\": "database/factories/"
    }
},
"autoload-dev": {
    "psr-4": {
        "JonesRussell\\NorthCloud\\Tests\\": "tests/"
    }
},
```

Run: `composer dump-autoload`

**Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Unit/Models/FactoryResolutionTest.php`
Expected: PASS (3 tests)

**Step 5: Commit**

```bash
git add composer.json tests/Unit/Models/FactoryResolutionTest.php
git commit -m "fix: move factory autoload to production so consumer apps can use ::factory()"
```

---

### Task 4: Add Linking Config Section

**Files:**
- Modify: `config/northcloud.php:43` (add `linking` section before `navigation`)
- Test: `tests/Unit/Config/LinkingConfigTest.php`

**Step 1: Write the failing test**

```php
<?php
// tests/Unit/Config/LinkingConfigTest.php

declare(strict_types=1);

it('has linking config with defaults', function () {
    expect(config('northcloud.linking.enabled'))->toBeFalse();
    expect(config('northcloud.linking.threshold'))->toBe(0.3);
    expect(config('northcloud.linking.weights.title_match'))->toBe(0.5);
    expect(config('northcloud.linking.weights.tag_overlap'))->toBe(0.3);
    expect(config('northcloud.linking.weights.metadata_match'))->toBe(0.2);
    expect(config('northcloud.linking.min_keyword_length'))->toBe(3);
});
```

**Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Unit/Config/LinkingConfigTest.php`
Expected: FAIL — config key does not exist

**Step 3: Add the linking section to config/northcloud.php**

Add after line 43 (after `tags` section, before `navigation`):

```php
'linking' => [
    'enabled' => false,
    'threshold' => 0.3,
    'weights' => [
        'title_match' => 0.5,
        'tag_overlap' => 0.3,
        'metadata_match' => 0.2,
    ],
    'min_keyword_length' => 3,
],
```

Also add `'linking'` to the `KNOWN_TOP_KEYS` array in `ConfigValidator.php` (already done in Task 1).

**Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Unit/Config/LinkingConfigTest.php`
Expected: PASS (1 test)

**Step 5: Commit**

```bash
git add config/northcloud.php tests/Unit/Config/LinkingConfigTest.php
git commit -m "feat: add northcloud.linking config section for article-entity linking"
```

---

### Task 5: Add Strict Types to All Package Files

**Files:**
- Modify: Every `.php` file in `src/` and `database/` missing `declare(strict_types=1)`

**Step 1: Find files missing strict_types**

Run: `grep -rL "declare(strict_types=1)" src/ database/`

**Step 2: Add `declare(strict_types=1)` to each file**

For each file found, add `declare(strict_types=1);` as the first statement after `<?php`.

**Step 3: Run full test suite**

Run: `vendor/bin/pest`
Expected: All tests PASS. If any fail due to type coercion, fix the coercion issue (cast explicitly).

**Step 4: Commit**

```bash
git add -A
git commit -m "refactor: add declare(strict_types=1) to all package PHP files"
```

---

### Task 6: Enhance Install Command with Middleware Guidance

**Files:**
- Modify: `src/Console/Commands/NorthCloudInstall.php:55-57`
- Modify: `tests/Feature/NorthCloudInstallCommandTest.php`

**Step 1: Add middleware info to install command output**

After the "NorthCloud installed successfully." line in `handle()`, add:

```php
info('');
note('Middleware: The "northcloud-admin" middleware alias is available for protecting admin routes.');
note('Usage: Route::middleware(\'northcloud-admin\')->group(function () { ... });');
```

**Step 2: Update existing install test to check for the message**

Add to the existing test:

```php
it('shows middleware guidance on install', function () {
    $this->artisan('northcloud:install', ['--skip-ui' => true])
        ->expectsOutputToContain('northcloud-admin')
        ->assertSuccessful();
});
```

**Step 3: Run test**

Run: `vendor/bin/pest tests/Feature/NorthCloudInstallCommandTest.php`
Expected: PASS

**Step 4: Commit**

```bash
git add src/Console/Commands/NorthCloudInstall.php tests/Feature/NorthCloudInstallCommandTest.php
git commit -m "feat: add middleware guidance to northcloud:install output"
```

---

### Task 7: Version Bump and Full Test Run

**Files:**
- Modify: `composer.json:3` (version to 0.7.0)

**Step 1: Run full test suite**

Run: `vendor/bin/pest`
Expected: All tests PASS

**Step 2: Run Pint**

Run: `vendor/bin/pint`

**Step 3: Bump version in composer.json**

Change `"version": "0.6.0"` to `"version": "0.7.0"`.

**Step 4: Commit and tag**

```bash
git add -A
git commit -m "chore: bump version to 0.7.0 — config validation, nav API, factory fix, linking config, strict types"
git tag v0.7.0
```

---

## Phase 2: movies-of-war.com Consumer App Fixes

All work in `/home/jones/dev/movies-of-war.com`. Tests run with `ddev artisan test --compact`.

---

### Task 8: Fix Critical Namespace Bug

**Files:**
- Modify: `app/Listeners/LinkArticlesToMovies.php:7`
- Modify: `app/Providers/AppServiceProvider.php:19`

**Step 1: Fix the namespace in LinkArticlesToMovies.php**

Change line 7:
```php
// FROM:
use JonesRussell\NorthcloudLaravel\Events\ArticleProcessed;
// TO:
use JonesRussell\NorthCloud\Events\ArticleProcessed;
```

**Step 2: Fix the namespace in AppServiceProvider.php**

Change line 19:
```php
// FROM:
use JonesRussell\NorthcloudLaravel\Events\ArticleProcessed;
// TO:
use JonesRussell\NorthCloud\Events\ArticleProcessed;
```

**Step 3: Run tests**

Run: `ddev artisan test --compact`
Expected: PASS

**Step 4: Commit**

```bash
git add app/Listeners/LinkArticlesToMovies.php app/Providers/AppServiceProvider.php
git commit -m "fix: correct NorthCloud namespace in event listener and service provider"
```

---

### Task 9: Fix Config Schema

**Files:**
- Modify: `config/northcloud.php` (full rewrite to match package schema)

**Step 1: Rewrite config/northcloud.php**

Replace the full file contents with:

```php
<?php

return [
    'migrations' => [
        'enabled' => false,
    ],

    'redis' => [
        'connection' => env('NORTHCLOUD_REDIS_CONNECTION', env('REDIS_ARTICLES_CONNECTION', 'default')),
        'channels' => [env('NORTHCLOUD_CHANNEL', env('REDIS_ARTICLES_CHANNEL', 'articles:war'))],
    ],

    'quality' => [
        'min_score' => (int) env('NORTHCLOUD_MIN_QUALITY_SCORE', 70),
        'enabled' => (bool) env('NORTHCLOUD_QUALITY_FILTER', true),
    ],

    'models' => [
        'article' => \App\Models\WarArticle::class,
        'news_source' => \JonesRussell\NorthCloud\Models\NewsSource::class,
        'tag' => \JonesRussell\NorthCloud\Models\Tag::class,
    ],

    'processors' => [
        \JonesRussell\NorthCloud\Processing\DefaultArticleProcessor::class,
    ],

    'processing' => [
        'sync' => (bool) env('NORTHCLOUD_PROCESS_SYNC', true),
    ],

    'content' => [
        'allowed_tags' => ['p', 'br', 'a', 'strong', 'em', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'],
    ],

    'tags' => [
        'default_type' => 'theme',
        'auto_create' => true,
        'allowed' => [],
    ],

    'linking' => [
        'enabled' => true,
        'threshold' => 0.3,
        'weights' => [
            'title_match' => 0.5,
            'tag_overlap' => 0.3,
            'metadata_match' => 0.2,
        ],
        'min_keyword_length' => 3,
    ],

    'navigation' => [
        'enabled' => true,
        'items' => [
            ['title' => 'Movies', 'route' => 'dashboard.movies', 'icon' => 'Film'],
            ['title' => 'TMDB Imports', 'route' => 'dashboard.tmdb.imports', 'icon' => 'Download'],
            ['title' => 'Featured', 'route' => 'dashboard.featured-slots', 'icon' => 'Star'],
            ['title' => 'Reviews', 'route' => 'dashboard.reviews', 'icon' => 'MessageSquare'],
        ],
    ],

    'articleable' => [
        'enabled' => true,
        'models' => [
            \App\Models\Movie::class => [
                'label' => 'Movie',
                'display' => 'title',
                'search' => ['title'],
            ],
        ],
    ],
];
```

**Changes from original:**
- `'channel'` → `'channels'` (array)
- Removed `'processing.processor'`
- Added root-level `'processors'` array
- Removed `'content.max_excerpt_length'`
- Added `'linking'` section
- Added `'navigation'` section with app-specific items

**Step 2: Run tests**

Run: `ddev artisan test --compact`
Expected: PASS

**Step 3: Commit**

```bash
git add config/northcloud.php
git commit -m "fix: align northcloud config schema with package v0.7.0"
```

---

### Task 10: Delete Local Admin Middleware and Switch to Package

**Files:**
- Delete: `app/Http/Middleware/EnsureUserIsAdmin.php`
- Modify: `bootstrap/app.php:4,30-32`
- Modify: `routes/web/admin.php:9`

**Step 1: Update bootstrap/app.php**

Remove line 4 (`use App\Http\Middleware\EnsureUserIsAdmin;`) and remove the alias block (lines 30-32):

```php
// Remove this entire block:
$middleware->alias([
    'admin' => EnsureUserIsAdmin::class,
]);
```

**Step 2: Update routes/web/admin.php line 9**

```php
// FROM:
Route::middleware('admin')->group(function () {
// TO:
Route::middleware('northcloud-admin')->group(function () {
```

**Step 3: Delete the local middleware file**

```bash
rm app/Http/Middleware/EnsureUserIsAdmin.php
```

**Step 4: Run tests**

Run: `ddev artisan test --compact`
Expected: PASS — admin route protection still works via package middleware

**Step 5: Commit**

```bash
git add bootstrap/app.php routes/web/admin.php
git rm app/Http/Middleware/EnsureUserIsAdmin.php
git commit -m "refactor: switch to package northcloud-admin middleware, remove local duplicate"
```

---

### Task 11: Extract Confidence Thresholds to Config

**Files:**
- Modify: `app/Listeners/LinkArticlesToMovies.php` (full file)

**Step 1: Rewrite LinkArticlesToMovies to use config values**

```php
<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\Movie;
use App\Models\WarArticle;
use Illuminate\Support\Str;
use JonesRussell\NorthCloud\Events\ArticleProcessed;

class LinkArticlesToMovies
{
    public function handle(ArticleProcessed $event): void
    {
        /** @var WarArticle $article */
        $article = $event->article;
        $keywords = $this->extractKeywords($article);

        $movies = Movie::query()
            ->published()
            ->where(function ($query) use ($article, $keywords): void {
                foreach ($keywords as $keyword) {
                    $query->orWhere('title', 'like', "%{$keyword}%");
                }

                if ($article->tags()->exists()) {
                    $tagSlugs = $article->tags->pluck('slug')->toArray();
                    $query->orWhereHas('tags', function ($tagQuery) use ($tagSlugs): void {
                        $tagQuery->whereIn('slug', $tagSlugs);
                    });
                }
            })
            ->get();

        $threshold = (float) config('northcloud.linking.threshold', 0.3);
        $movieData = [];

        foreach ($movies as $movie) {
            $confidence = $this->calculateConfidence($article, $movie, $keywords);

            if ($confidence > $threshold) {
                $movieData[$movie->id] = ['confidence' => $confidence];
            }
        }

        if ($movieData !== []) {
            $article->movies()->sync($movieData);
        }
    }

    /**
     * @return list<string>
     */
    protected function extractKeywords(WarArticle $article): array
    {
        $text = $article->title . ' ' . $article->content;
        $words = str_word_count(strtolower($text), 1);
        $minLength = (int) config('northcloud.linking.min_keyword_length', 3);

        $stopWords = ['the', 'and', 'for', 'are', 'but', 'not', 'you', 'all', 'can',
            'her', 'was', 'one', 'our', 'out', 'day', 'get', 'has', 'him', 'his', 'how',
            'man', 'new', 'now', 'old', 'see', 'two', 'way', 'who', 'boy', 'did', 'its',
            'let', 'put', 'say', 'she', 'too', 'use'];

        return array_values(array_filter($words, function ($word) use ($stopWords, $minLength): bool {
            return strlen($word) >= $minLength && ! in_array($word, $stopWords, true);
        }));
    }

    protected function calculateConfidence(WarArticle $article, Movie $movie, array $keywords): float
    {
        $confidence = 0.0;
        $titleWeight = (float) config('northcloud.linking.weights.title_match', 0.5);
        $tagWeight = (float) config('northcloud.linking.weights.tag_overlap', 0.3);
        $metadataWeight = (float) config('northcloud.linking.weights.metadata_match', 0.2);

        // Title match
        $movieTitleWords = explode(' ', strtolower($movie->title));
        $matchingWords = array_intersect($keywords, $movieTitleWords);
        if (count($matchingWords) > 0) {
            $confidence += $titleWeight * (count($matchingWords) / count($movieTitleWords));
        }

        // Tag overlap
        if ($article->tags()->exists() && $movie->tags()->exists()) {
            $articleTagSlugs = $article->tags->pluck('slug')->toArray();
            $movieTagSlugs = $movie->tags->pluck('slug')->toArray();
            $sharedTags = array_intersect($articleTagSlugs, $movieTagSlugs);

            if (count($sharedTags) > 0) {
                $confidence += $tagWeight * (count($sharedTags) / count($movieTagSlugs));
            }
        }

        // War era / metadata match
        if (isset($article->war_era) && isset($movie->conflict)) {
            if (Str::contains(strtolower($movie->conflict), strtolower($article->war_era))) {
                $confidence += $metadataWeight;
            }
        }

        return min($confidence, 1.0);
    }
}
```

**Step 2: Run tests**

Run: `ddev artisan test --compact`
Expected: PASS

**Step 3: Commit**

```bash
git add app/Listeners/LinkArticlesToMovies.php
git commit -m "refactor: extract confidence thresholds to northcloud.linking config"
```

---

### Task 12: Add Strict Types to All 25 Missing Files

**Files:**
- Modify: All 25 files listed in audit section B1

**Step 1: Add `declare(strict_types=1)` to each file**

For each file, add `declare(strict_types=1);` as line 3 (after `<?php` and before `namespace`).

Files (24 remaining after EnsureUserIsAdmin deletion in Task 10):
1. `app/Models/WarArticle.php`
2. `app/Models/FeaturedSlot.php`
3. `app/Models/User.php`
4. `app/Http/Middleware/AddCacheHeaders.php`
5. `app/Http/Middleware/HandleInertiaRequests.php`
6. `app/Http/Middleware/HandleAppearance.php`
7. `app/Http/Controllers/WatchlistController.php`
8. `app/Http/Controllers/Settings/PasswordController.php`
9. `app/Http/Controllers/Settings/TwoFactorAuthenticationController.php`
10. `app/Http/Controllers/Settings/ProfileController.php`
11. `app/Http/Requests/ImportTmdbMoviesRequest.php`
12. `app/Http/Requests/Settings/PasswordUpdateRequest.php`
13. `app/Http/Requests/Settings/ProfileDeleteRequest.php`
14. `app/Http/Requests/Settings/TwoFactorAuthenticationRequest.php`
15. `app/Http/Requests/Settings/ProfileUpdateRequest.php`
16. `app/Actions/Fortify/CreateNewUser.php`
17. `app/Actions/Fortify/PasswordValidationRules.php`
18. `app/Actions/Fortify/ResetUserPassword.php`
19. `app/Console/Commands/ChangeUserPassword.php`
20. `app/Console/Commands/MakeUserAdmin.php`
21. `app/Console/Commands/GenerateWarMoviePost.php`
22. `app/Providers/FortifyServiceProvider.php`
23. `app/Jobs/ImportTmdbMoviesJob.php`

Note: `app/Listeners/LinkArticlesToMovies.php` was already fixed in Task 11.

**Step 2: Run full test suite**

Run: `ddev artisan test --compact`
Expected: PASS. If any test fails due to type coercion, fix the coercion (add explicit `(int)`, `(string)`, etc. casts).

**Step 3: Commit**

```bash
git add app/
git commit -m "refactor: add declare(strict_types=1) to all 24 remaining app/ files"
```

---

### Task 13: Fix Scope Method Type Safety

**Files:**
- Modify: `app/Models/FeaturedSlot.php:29-37`
- Modify: `app/Models/WarArticle.php:45-48`

**Step 1: Fix FeaturedSlot scopes**

Add `use Illuminate\Database\Eloquent\Builder;` to imports, then update scopes:

```php
/**
 * @param  Builder<FeaturedSlot>  $query
 * @return Builder<FeaturedSlot>
 */
public function scopeActive(Builder $query): Builder
{
    return $query; // All slots are active
}

/**
 * @param  Builder<FeaturedSlot>  $query
 * @return Builder<FeaturedSlot>
 */
public function scopeSlot(Builder $query, string $slot): Builder
{
    return $query->where('slot', $slot);
}
```

**Step 2: Fix WarArticle scope**

Add `use Illuminate\Database\Eloquent\Builder;` to imports, then update scope:

```php
/**
 * @param  Builder<WarArticle>  $query
 * @return Builder<WarArticle>
 */
public function scopeWarEra(Builder $query, string $era): Builder
{
    return $query->where('war_era', $era);
}
```

**Step 3: Run tests**

Run: `ddev artisan test --compact`
Expected: PASS

**Step 4: Commit**

```bash
git add app/Models/FeaturedSlot.php app/Models/WarArticle.php
git commit -m "refactor: add Builder type hints and PHPDoc generics to scope methods"
```

---

### Task 14: Minor Cleanup — Pagination and Rate Limiting

**Files:**
- Create: `app/Http/Controllers/Concerns/ResolvesPagination.php`
- Modify: `app/Http/Controllers/DashboardController.php` (use trait)
- Modify: `app/Http/Controllers/Admin/MovieController.php` (use trait)

**Step 1: Create the shared trait**

```php
<?php
// app/Http/Controllers/Concerns/ResolvesPagination.php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait ResolvesPagination
{
    /**
     * @param  list<int>  $allowed
     */
    protected function resolvePerPage(Request $request, int $default = 20, array $allowed = [10, 20, 50, 100]): int
    {
        $perPage = (int) $request->input('per_page', $default);

        return in_array($perPage, $allowed, true) ? $perPage : $default;
    }
}
```

**Step 2: Use the trait in DashboardController**

Replace `resolveTmdbImportsPerPage()` with the trait, passing `default: 24` and `allowed: [10, 20, 24, 50, 100]`.

**Step 3: Use the trait in Admin/MovieController**

Replace `resolvePerPage()` with the trait method.

**Step 4: Run tests**

Run: `ddev artisan test --compact`
Expected: PASS

**Step 5: Commit**

```bash
git add app/Http/Controllers/Concerns/ResolvesPagination.php \
  app/Http/Controllers/DashboardController.php \
  app/Http/Controllers/Admin/MovieController.php
git commit -m "refactor: extract shared pagination resolver into ResolvesPagination trait"
```

---

### Task 15: Register Custom Navigation via NorthCloud API

**Files:**
- Modify: `app/Providers/AppServiceProvider.php:34-38`

**Step 1: Add navigation registration to boot()**

Add to `AppServiceProvider::boot()`:

```php
use JonesRussell\NorthCloud\Facades\NorthCloud;

// In boot(), after registerObservers():
NorthCloud::registerNavigation([
    ['title' => 'X Posts', 'route' => 'dashboard.x-posts.index', 'icon' => 'MessageCircle'],
]);
```

This registers X Posts dynamically (other nav items are in config). Only use the API for items that are conditional or from separate packages.

**Step 2: Run tests**

Run: `ddev artisan test --compact`
Expected: PASS

**Step 3: Commit**

```bash
git add app/Providers/AppServiceProvider.php
git commit -m "feat: register X Posts nav item via NorthCloud::registerNavigation()"
```

---

### Task 16: Version Bump and Final Verification

**Files:**
- Modify: `composer.json:15` (northcloud-laravel version requirement)

**Step 1: Update composer.json**

Change `"jonesrussell/northcloud-laravel": "^0.6"` to `"jonesrussell/northcloud-laravel": "^0.7"`.

**Step 2: Run composer update**

Run: `ddev composer update jonesrussell/northcloud-laravel`

**Step 3: Run Pint**

Run: `ddev exec vendor/bin/pint --dirty`

**Step 4: Run full test suite**

Run: `ddev artisan test --compact`
Expected: All tests PASS

**Step 5: Run frontend checks**

Run: `npm run check`
Expected: PASS

**Step 6: Commit**

```bash
git add composer.json composer.lock
git commit -m "chore: upgrade northcloud-laravel to ^0.7 for full platform compliance"
```

---

## Phase 3: Final Compliance Verification

### Task 17: Re-run Compliance Audit

Run the full NorthCloud Platform Standard audit against the updated codebase. Expected result: **96%+ compliance** (Compliant).

Remaining items that may still be Partial:
- B6.1 (Vue filename convention — PascalCase vs kebab-case for `.vue` files is a project convention decision, not a bug)
- B7.3 (Package factory usage — now fixed by Task 3, should be Pass)
- A8.2 (Admin pages — movie admin is app-specific, appropriate Partial)

---

**Total: 17 tasks across 3 phases**
- Phase 1 (Package): Tasks 1-7
- Phase 2 (Consumer): Tasks 8-16
- Phase 3 (Verification): Task 17
