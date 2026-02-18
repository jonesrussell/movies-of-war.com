# Sidebar Auto-Registration & Article Association Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Enable sidebar auto-registration from northcloud-laravel package props, add polymorphic article-domain-model association to the package, and integrate articles with movies in MoW.

**Architecture:** The package shares nav items via Inertia props (already working); the app consumes them via a composable. For articles, the package adds a polymorphic `morphTo` on Article with config-driven associatable models. Consumer apps opt in and configure which models to associate. MoW gets public article pages and related articles on movie detail.

**Tech Stack:** Laravel 12, northcloud-laravel v0.6.0, Vue 3, Inertia v2, Pest 4, Tailwind v4

**Important context:**
- Package dev directory: `/home/jones/dev/northcloud-laravel/`
- App directory: `/home/jones/dev/movies-of-war.com/`
- The app uses `ddev` for all CLI commands (e.g., `ddev artisan`, `ddev composer`)
- The app's `WarArticle` model currently extends `JonesRussell\NorthcloudLaravel\Models\Article` (OLD namespace). The v0.5 package namespace is `JonesRussell\NorthCloud`. This must be fixed.
- Movie model already has a `BelongsToMany(WarArticle::class, 'article_movie')` relationship and WarArticle has the inverse. The polymorphic design replaces this with `morphTo`/`morphMany`.
- The app's `config/northcloud.php` has `migrations.enabled => false` — the app manages its own migrations.
- Run `vendor/bin/pint --dirty` on PHP files before committing.
- Run `npm run lint` on TS/Vue files before committing.

---

## Phase 1: Package — Polymorphic Articleable (northcloud-laravel)

All Phase 1 work happens in `/home/jones/dev/northcloud-laravel/`.

### Task 1: Add articleable migration

**Files:**
- Create: `database/migrations/2025_01_01_000005_add_articleable_to_articles_table.php`

**Step 1: Create the migration file**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->nullableMorphs('articleable');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropMorphs('articleable');
        });
    }
};
```

**Step 2: Commit**

```bash
git add database/migrations/2025_01_01_000005_add_articleable_to_articles_table.php
git commit -m "feat: add articleable polymorphic migration"
```

---

### Task 2: Add articleable relationship to Article model + test

**Files:**
- Modify: `src/Models/Article.php` (add `articleable()` method + `articleable_type`/`articleable_id` to fillable)
- Create: `tests/Unit/Models/ArticleArticleableTest.php`

**Step 1: Write the failing test**

Create `tests/Unit/Models/ArticleArticleableTest.php`:

```php
<?php

use Illuminate\Database\Eloquent\Relations\MorphTo;
use JonesRussell\NorthCloud\Models\Article;

it('has an articleable morph-to relationship', function () {
    $article = new Article;

    expect($article->articleable())->toBeInstanceOf(MorphTo::class);
});

it('allows null articleable for standalone articles', function () {
    $article = Article::factory()->create();

    expect($article->articleable)->toBeNull();
    expect($article->articleable_type)->toBeNull();
    expect($article->articleable_id)->toBeNull();
});
```

**Step 2: Run test to verify it fails**

```bash
vendor/bin/pest tests/Unit/Models/ArticleArticleableTest.php --compact
```

Expected: FAIL — `articleable()` method doesn't exist.

**Step 3: Add the relationship to Article model**

In `src/Models/Article.php`, add import and method:

```php
use Illuminate\Database\Eloquent\Relations\MorphTo;
```

Add method after `tags()`:

```php
public function articleable(): MorphTo
{
    return $this->morphTo();
}
```

Add to `$fillable` array:

```php
'articleable_type', 'articleable_id',
```

**Step 4: Run test to verify it passes**

```bash
vendor/bin/pest tests/Unit/Models/ArticleArticleableTest.php --compact
```

Expected: PASS

**Step 5: Run pint and commit**

```bash
vendor/bin/pint --dirty
git add src/Models/Article.php tests/Unit/Models/ArticleArticleableTest.php
git commit -m "feat: add articleable morphTo relationship to Article"
```

---

### Task 3: Add articleable config

**Files:**
- Modify: `config/northcloud.php`
- Modify: `src/NorthCloudServiceProvider.php` (deep-merge new config key)
- Create: `tests/Unit/Config/ArticleableConfigTest.php`

**Step 1: Write the failing test**

Create `tests/Unit/Config/ArticleableConfigTest.php`:

```php
<?php

it('has articleable config with defaults', function () {
    $config = config('northcloud.articleable');

    expect($config)->toBeArray();
    expect($config['enabled'])->toBeFalse();
    expect($config['models'])->toBeArray()->toBeEmpty();
});
```

**Step 2: Run test to verify it fails**

```bash
vendor/bin/pest tests/Unit/Config/ArticleableConfigTest.php --compact
```

Expected: FAIL — `northcloud.articleable` is null.

**Step 3: Add config section**

In `config/northcloud.php`, add after the `'navigation'` key:

```php
'articleable' => [
    'enabled' => false,
    'models' => [],
],
```

In `src/NorthCloudServiceProvider.php`, in the `register()` method, add deep-merge for the new key (follow the pattern used for `northcloud.admin`, `northcloud.users`, etc.):

```php
$this->mergeConfigFrom(__DIR__.'/../config/northcloud.php', 'northcloud');

// After existing deep-merges, add:
if (! array_key_exists('articleable', config('northcloud', []))) {
    config(['northcloud.articleable' => config('northcloud.articleable', [
        'enabled' => false,
        'models' => [],
    ])]);
}
```

Note: Check the existing deep-merge pattern in `register()` and follow it exactly.

**Step 4: Run test to verify it passes**

```bash
vendor/bin/pest tests/Unit/Config/ArticleableConfigTest.php --compact
```

Expected: PASS

**Step 5: Run pint and commit**

```bash
vendor/bin/pint --dirty
git add config/northcloud.php src/NorthCloudServiceProvider.php tests/Unit/Config/ArticleableConfigTest.php
git commit -m "feat: add articleable config section"
```

---

### Task 4: Add articleable field to ArticleResource

**Files:**
- Modify: `src/Admin/ArticleResource.php`
- Create: `tests/Unit/Admin/ArticleResourceArticleableTest.php`

**Step 1: Write the failing test**

Create `tests/Unit/Admin/ArticleResourceArticleableTest.php`:

```php
<?php

use JonesRussell\NorthCloud\Admin\ArticleResource;

it('does not include articleable field when disabled', function () {
    config(['northcloud.articleable.enabled' => false]);

    $resource = new ArticleResource;
    $fieldNames = array_column($resource->fields(), 'name');

    expect($fieldNames)->not->toContain('articleable');
});

it('includes articleable field when enabled', function () {
    config(['northcloud.articleable.enabled' => true]);
    config(['northcloud.articleable.models' => [
        'App\\Models\\Movie' => [
            'label' => 'Movie',
            'display' => 'title',
            'search' => ['title'],
        ],
    ]]);

    $resource = new ArticleResource;
    $fieldNames = array_column($resource->fields(), 'name');

    expect($fieldNames)->toContain('articleable');

    $articleableField = collect($resource->fields())->firstWhere('name', 'articleable');
    expect($articleableField['type'])->toBe('articleable');
    expect($articleableField['required'])->toBeFalse();
});
```

**Step 2: Run test to verify it fails**

```bash
vendor/bin/pest tests/Unit/Admin/ArticleResourceArticleableTest.php --compact
```

Expected: FAIL — field not found.

**Step 3: Add articleable field to ArticleResource::fields()**

In `src/Admin/ArticleResource.php`, at the end of the `fields()` method, before the closing `];`, add:

```php
if (config('northcloud.articleable.enabled', false)) {
    $fields[] = [
        'name' => 'articleable',
        'type' => 'articleable',
        'label' => 'Associated With',
        'required' => false,
        'rules' => ['nullable'],
    ];
}

return $fields;
```

Change the existing `return [...]` to `$fields = [...]` at the start.

**Step 4: Run test to verify it passes**

```bash
vendor/bin/pest tests/Unit/Admin/ArticleResourceArticleableTest.php --compact
```

Expected: PASS

**Step 5: Run pint and commit**

```bash
vendor/bin/pint --dirty
git add src/Admin/ArticleResource.php tests/Unit/Admin/ArticleResourceArticleableTest.php
git commit -m "feat: add conditional articleable field to ArticleResource"
```

---

### Task 5: Add search-associatable endpoint to ArticleController

**Files:**
- Modify: `src/Http/Controllers/Admin/ArticleController.php`
- Modify: `routes/admin.php`
- Create: `tests/Feature/Admin/ArticleSearchAssociatableTest.php`

**Step 1: Write the failing test**

Create `tests/Feature/Admin/ArticleSearchAssociatableTest.php`:

```php
<?php

use Illuminate\Foundation\Auth\User as AuthUser;

beforeEach(function () {
    config(['northcloud.articleable.enabled' => true]);
});

it('returns 404 when articleable is disabled', function () {
    config(['northcloud.articleable.enabled' => false]);

    $admin = AuthUser::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->getJson(route('dashboard.articles.search-associatable', ['model' => 'App\\Models\\Movie', 'q' => 'test']))
        ->assertNotFound();
});
```

Note: This test may need adjustment based on how the test app's User model and admin factories are set up. Check `tests/TestCase.php` or `tests/Pest.php` for the base user setup pattern used by existing feature tests like `tests/Feature/Admin/UserManagementTest.php`.

**Step 2: Run test to verify it fails**

```bash
vendor/bin/pest tests/Feature/Admin/ArticleSearchAssociatableTest.php --compact
```

Expected: FAIL — route not found.

**Step 3: Add route and controller method**

In `routes/admin.php`, add before the resource routes section:

```php
// Search associatable models
Route::get('search-associatable', [$controller, 'searchAssociatable'])->name('search-associatable');
```

In `src/Http/Controllers/Admin/ArticleController.php`, add method:

```php
public function searchAssociatable(Request $request): \Illuminate\Http\JsonResponse
{
    if (! config('northcloud.articleable.enabled', false)) {
        abort(404);
    }

    $request->validate([
        'model' => 'required|string',
        'q' => 'nullable|string|max:255',
    ]);

    $modelClass = $request->input('model');
    $models = config('northcloud.articleable.models', []);

    if (! isset($models[$modelClass]) || ! class_exists($modelClass)) {
        abort(422, 'Invalid model type.');
    }

    $config = $models[$modelClass];
    $query = $modelClass::query();

    if ($q = $request->input('q')) {
        $searchColumns = $config['search'] ?? ['name'];
        $query->where(function ($qb) use ($searchColumns, $q) {
            foreach ($searchColumns as $col) {
                $qb->orWhere($col, 'LIKE', "%{$q}%");
            }
        });
    }

    $displayField = $config['display'] ?? 'name';

    return response()->json(
        $query->select(['id', $displayField])
            ->orderBy($displayField)
            ->limit(20)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'label' => $item->{$displayField},
            ])
    );
}
```

**Step 4: Run test to verify it passes**

```bash
vendor/bin/pest tests/Feature/Admin/ArticleSearchAssociatableTest.php --compact
```

Expected: PASS

**Step 5: Handle articleable in store/update**

In `ArticleController::store()`, after `$this->syncRelations($article, $relations);`, add:

```php
$this->syncArticleable($article, $request);
```

In `ArticleController::update()`, after `$this->syncRelations($article, $relations);`, add:

```php
$this->syncArticleable($article, $request);
```

Add private method:

```php
private function syncArticleable(Model $article, Request $request): void
{
    if (! config('northcloud.articleable.enabled', false)) {
        return;
    }

    if ($request->has('articleable_type') && $request->has('articleable_id')) {
        $article->update([
            'articleable_type' => $request->input('articleable_type') ?: null,
            'articleable_id' => $request->input('articleable_id') ?: null,
        ]);
    }
}
```

Also update `create()` and `edit()` to pass articleable options:

In `create()`, add to the Inertia::render props:

```php
'articleableOptions' => $this->getArticleableOptions(),
```

In `edit()`, add to the Inertia::render props:

```php
'articleableOptions' => $this->getArticleableOptions(),
```

Add helper:

```php
private function getArticleableOptions(): array
{
    if (! config('northcloud.articleable.enabled', false)) {
        return [];
    }

    $options = [];
    foreach (config('northcloud.articleable.models', []) as $modelClass => $config) {
        $options[] = [
            'model' => $modelClass,
            'label' => $config['label'] ?? class_basename($modelClass),
            'display' => $config['display'] ?? 'name',
        ];
    }

    return $options;
}
```

**Step 6: Run pint and commit**

```bash
vendor/bin/pint --dirty
git add src/Http/Controllers/Admin/ArticleController.php routes/admin.php tests/Feature/Admin/ArticleSearchAssociatableTest.php
git commit -m "feat: add search-associatable endpoint and articleable handling in CRUD"
```

---

### Task 6: Add articleable field type to ArticleForm.vue

**Files:**
- Modify: `resources/js/components/admin/ArticleForm.vue`

**Step 1: Add the articleable field type rendering**

In `ArticleForm.vue`, add new props for articleable:

Update the Props interface to add:

```typescript
articleableOptions?: Array<{ model: string; label: string; display: string }>;
```

Add these to `withDefaults`:

```typescript
articleableOptions: () => [],
```

Add state for the searchable dropdown (after the existing script setup):

```typescript
import { ref, watch } from 'vue';

const articleableSearch = ref('');
const articleableResults = ref<Array<{ id: number; label: string }>>([]);
const articleableLoading = ref(false);
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const searchAssociatable = async (model: string, q: string) => {
    if (!q || q.length < 2) {
        articleableResults.value = [];
        return;
    }
    articleableLoading.value = true;
    try {
        const response = await fetch(
            `/dashboard/articles/search-associatable?model=${encodeURIComponent(model)}&q=${encodeURIComponent(q)}`
        );
        articleableResults.value = await response.json();
    } finally {
        articleableLoading.value = false;
    }
};

const debouncedSearch = (model: string, q: string) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => searchAssociatable(model, q), 300);
};

const selectArticleable = (model: string, id: number, label: string) => {
    updateField('articleable_type', model);
    updateField('articleable_id', id);
    articleableSearch.value = label;
    articleableResults.value = [];
};

const clearArticleable = () => {
    updateField('articleable_type', null);
    updateField('articleable_id', null);
    articleableSearch.value = '';
    articleableResults.value = [];
};
```

Add template block after the `select` field type (before the error message `<p>`):

```html
<!-- Articleable (searchable association) -->
<template v-else-if="field.type === 'articleable' && articleableOptions.length > 0">
    <Label :for="field.name">
        {{ field.label }}
    </Label>
    <div class="space-y-2">
        <div v-for="opt in articleableOptions" :key="opt.model" class="relative">
            <div class="flex gap-2">
                <Input
                    :id="field.name"
                    v-model="articleableSearch"
                    type="text"
                    :placeholder="`Search ${opt.label.toLowerCase()}...`"
                    @input="debouncedSearch(opt.model, articleableSearch)"
                />
                <Button
                    v-if="modelValue.articleable_id"
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="clearArticleable"
                >
                    Clear
                </Button>
            </div>
            <!-- Results dropdown -->
            <div
                v-if="articleableResults.length > 0"
                class="absolute z-10 mt-1 w-full rounded-md border border-input bg-popover shadow-lg"
            >
                <button
                    v-for="result in articleableResults"
                    :key="result.id"
                    type="button"
                    class="w-full px-3 py-2 text-left text-sm hover:bg-accent"
                    @click="selectArticleable(opt.model, result.id, result.label)"
                >
                    {{ result.label }}
                </button>
            </div>
            <p v-if="articleableLoading" class="text-xs text-muted-foreground">Searching...</p>
        </div>
    </div>
</template>
```

**Step 2: Commit**

```bash
git add resources/js/components/admin/ArticleForm.vue
git commit -m "feat: add articleable searchable dropdown to ArticleForm"
```

---

### Task 7: Run full package test suite + version bump

**Files:**
- Modify: `composer.json` (version bump)

**Step 1: Run all package tests**

```bash
vendor/bin/pest --compact
```

Expected: All tests pass.

**Step 2: Bump version**

In `composer.json`, change `"version": "0.5.0"` to `"version": "0.6.0"`.

**Step 3: Run pint on all changed files**

```bash
vendor/bin/pint --dirty
```

**Step 4: Commit and tag**

```bash
git add composer.json
git commit -m "chore: bump version to 0.6.0 — articleable polymorphic association"
git tag v0.6.0
```

---

## Phase 2: App — Sidebar Auto-Registration

All Phase 2 work happens in `/home/jones/dev/movies-of-war.com/`.

### Task 8: Create useNorthcloudNav composable

**Files:**
- Create: `resources/js/composables/useNorthcloudNav.ts`

**Step 1: Create the composable**

```typescript
import type { NavItem } from '@/types';

import { usePage } from '@inertiajs/vue3';
import { FileText, Users, type LucideIcon } from 'lucide-vue-next';
import { computed, type ComputedRef } from 'vue';

interface NorthcloudNavItem {
    title: string;
    href: string;
    icon: string;
}

const iconMap: Record<string, LucideIcon> = {
    FileText,
    Users,
};

export function useNorthcloudNav(): { items: ComputedRef<NavItem[]> } {
    const page = usePage();

    const items = computed<NavItem[]>(() => {
        const northcloud = page.props.northcloud as
            | { navigation?: NorthcloudNavItem[] }
            | undefined;
        const nav = northcloud?.navigation ?? [];

        return nav
            .map((item) => ({
                title: item.title,
                href: item.href,
                icon: iconMap[item.icon],
            }))
            .filter((item) => item.icon !== undefined);
    });

    return { items };
}
```

**Step 2: Commit**

```bash
git add resources/js/composables/useNorthcloudNav.ts
git commit -m "feat: add useNorthcloudNav composable for sidebar auto-registration"
```

---

### Task 9: Wire composable into AppSidebar.vue

**Files:**
- Modify: `resources/js/components/AppSidebar.vue`

**Step 1: Update AppSidebar.vue**

Import the composable (after the existing imports):

```typescript
import { useNorthcloudNav } from '@/composables/useNorthcloudNav';
```

Add a `Package` icon import (for the NorthCloud group icon). Add to the lucide imports:

```typescript
Package,
```

Call the composable (after `const auth = ...`):

```typescript
const { items: northcloudItems } = useNorthcloudNav();
```

In the admin section of `mainNavItems`, **replace** the hardcoded Users entry:

```typescript
{
    title: 'Users',
    href: '/dashboard/users',
    icon: Users,
},
```

**with** the NorthCloud group:

```typescript
...(northcloudItems.value.length > 0
    ? [
          {
              title: 'NorthCloud',
              icon: Package,
              items: northcloudItems.value,
          },
      ]
    : []),
```

Remove the `Users` icon from the lucide-vue-next import list (it's now imported by the composable). Keep all other icons.

**Step 2: Run lint**

```bash
npm run lint
```

**Step 3: Build and verify**

```bash
npm run build
```

**Step 4: Commit**

```bash
git add resources/js/components/AppSidebar.vue
git commit -m "feat: replace hardcoded Users nav with auto-registered NorthCloud group"
```

---

### Task 10: Write sidebar test

**Files:**
- Create: `tests/Feature/SidebarNavigationTest.php`

**Step 1: Write the test**

```bash
ddev artisan make:test SidebarNavigationTest --pest --no-interaction
```

Edit `tests/Feature/SidebarNavigationTest.php`:

```php
<?php

use App\Models\User;

test('admin dashboard page includes northcloud navigation props', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $response = $this->actingAs($admin)->get('/dashboard');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('northcloud')
        ->has('northcloud.navigation')
    );
});

test('non-admin dashboard page includes northcloud navigation props', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('northcloud')
    );
});
```

**Step 2: Run the test**

```bash
ddev artisan test --compact tests/Feature/SidebarNavigationTest.php
```

Expected: PASS (the service provider already shares `northcloud` props via Inertia).

**Step 3: Commit**

```bash
git add tests/Feature/SidebarNavigationTest.php
git commit -m "test: add sidebar northcloud navigation tests"
```

---

## Phase 3: App — Article Integration

All Phase 3 work happens in `/home/jones/dev/movies-of-war.com/`.

### Task 11: Update northcloud-laravel dependency + fix namespace

**Files:**
- Modify: `composer.json` (bump northcloud-laravel to `^0.6`)
- Modify: `config/northcloud.php` (fix namespace references, add articleable config)
- Modify: `app/Models/WarArticle.php` (fix namespace import)

**Step 1: Update composer dependency**

```bash
ddev composer require jonesrussell/northcloud-laravel:^0.6 --no-interaction
```

**Step 2: Fix WarArticle namespace**

In `app/Models/WarArticle.php`, change:

```php
use JonesRussell\NorthcloudLaravel\Models\Article as BaseArticle;
```

to:

```php
use JonesRussell\NorthCloud\Models\Article as BaseArticle;
```

**Step 3: Fix config namespace references**

In `config/northcloud.php`, update the models section to use correct namespace:

```php
'models' => [
    'article' => \App\Models\WarArticle::class,
    'news_source' => \JonesRussell\NorthCloud\Models\NewsSource::class,
    'tag' => \JonesRussell\NorthCloud\Models\Tag::class,
],
```

Also fix the processing config if it references the old namespace.

**Step 4: Add articleable config**

In `config/northcloud.php`, add:

```php
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
```

**Step 5: Run pint and commit**

```bash
ddev exec vendor/bin/pint --dirty
git add composer.json composer.lock config/northcloud.php app/Models/WarArticle.php
git commit -m "chore: upgrade northcloud-laravel to v0.6, fix namespace, add articleable config"
```

---

### Task 12: Add articleable migration to app + update WarArticle model

**Files:**
- Create: migration via artisan
- Modify: `app/Models/WarArticle.php` (add `articleable_type`, `articleable_id` to fillable)

**Step 1: Create migration**

```bash
ddev artisan make:migration add_articleable_to_articles_table --table=articles --no-interaction
```

Edit the generated migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->nullableMorphs('articleable');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropMorphs('articleable');
        });
    }
};
```

**Step 2: Update WarArticle model**

In `app/Models/WarArticle.php`, add to `$fillable`:

```php
'articleable_type', 'articleable_id',
```

Replace the `movies()` many-to-many relationship with reference to the parent's `articleable()` (inherited from base Article model). Remove the `movies()` method entirely — the polymorphic `articleable()` from the base class handles this now.

**Step 3: Run migration**

```bash
ddev artisan migrate
```

**Step 4: Run pint and commit**

```bash
ddev exec vendor/bin/pint --dirty
git add database/migrations/*_add_articleable_to_articles_table.php app/Models/WarArticle.php
git commit -m "feat: add articleable migration and update WarArticle model"
```

---

### Task 13: Update Movie model — replace many-to-many with morphMany

**Files:**
- Modify: `app/Models/Movie.php`
- Create: `tests/Feature/MovieArticlesTest.php`

**Step 1: Write the failing test**

```bash
ddev artisan make:test MovieArticlesTest --pest --no-interaction
```

Edit `tests/Feature/MovieArticlesTest.php`:

```php
<?php

use App\Models\Movie;
use App\Models\WarArticle;
use JonesRussell\NorthCloud\Models\NewsSource;

test('movie has morphMany articles relationship', function () {
    $movie = Movie::factory()->published()->create();
    $source = NewsSource::factory()->create();

    $article = WarArticle::create([
        'news_source_id' => $source->id,
        'title' => 'Article about the movie',
        'slug' => 'article-about-the-movie',
        'url' => 'https://example.com/article',
        'content' => 'Content here.',
        'status' => 'published',
        'published_at' => now(),
        'articleable_type' => Movie::class,
        'articleable_id' => $movie->id,
    ]);

    expect($movie->articles)->toHaveCount(1);
    expect($movie->articles->first()->id)->toBe($article->id);
});

test('article articleable resolves to movie', function () {
    $movie = Movie::factory()->published()->create();
    $source = NewsSource::factory()->create();

    $article = WarArticle::create([
        'news_source_id' => $source->id,
        'title' => 'Article about the movie',
        'slug' => 'article-about-movie',
        'url' => 'https://example.com/article-2',
        'content' => 'Content here.',
        'status' => 'published',
        'published_at' => now(),
        'articleable_type' => Movie::class,
        'articleable_id' => $movie->id,
    ]);

    expect($article->articleable)->toBeInstanceOf(Movie::class);
    expect($article->articleable->id)->toBe($movie->id);
});

test('movie articles only returns published articles', function () {
    $movie = Movie::factory()->published()->create();
    $source = NewsSource::factory()->create();

    WarArticle::create([
        'news_source_id' => $source->id,
        'title' => 'Published article',
        'slug' => 'published-article',
        'url' => 'https://example.com/published',
        'content' => 'Content.',
        'status' => 'published',
        'published_at' => now()->subDay(),
        'articleable_type' => Movie::class,
        'articleable_id' => $movie->id,
    ]);

    WarArticle::create([
        'news_source_id' => $source->id,
        'title' => 'Draft article',
        'slug' => 'draft-article',
        'url' => 'https://example.com/draft',
        'content' => 'Content.',
        'status' => 'draft',
        'published_at' => null,
        'articleable_type' => Movie::class,
        'articleable_id' => $movie->id,
    ]);

    expect($movie->articles()->published()->count())->toBe(1);
});
```

**Step 2: Run test to verify it fails**

```bash
ddev artisan test --compact tests/Feature/MovieArticlesTest.php
```

Expected: FAIL — `articles()` returns BelongsToMany, not MorphMany.

**Step 3: Update Movie model**

In `app/Models/Movie.php`:

Replace the import:

```php
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
```

Add (if not already present):

```php
use Illuminate\Database\Eloquent\Relations\MorphMany;
```

Replace the `articles()` method:

```php
public function articles(): MorphMany
{
    return $this->morphMany(
        config('northcloud.models.article', \JonesRussell\NorthCloud\Models\Article::class),
        'articleable'
    );
}
```

Note: Keep the `BelongsToMany` import if other relationships (like `tags()`, `watchlistedBy()`) still use it.

**Step 4: Run test to verify it passes**

```bash
ddev artisan test --compact tests/Feature/MovieArticlesTest.php
```

Expected: PASS

**Step 5: Run pint and commit**

```bash
ddev exec vendor/bin/pint --dirty
git add app/Models/Movie.php tests/Feature/MovieArticlesTest.php
git commit -m "feat: replace article many-to-many with morphMany on Movie"
```

---

### Task 14: Create public ArticleController + routes

**Files:**
- Create: `app/Http/Controllers/ArticleController.php`
- Create: `routes/web/articles.php`
- Modify: `routes/web.php`

**Step 1: Create controller**

```bash
ddev artisan make:controller ArticleController --no-interaction
```

Edit `app/Http/Controllers/ArticleController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ArticleController extends Controller
{
    public function index(Request $request): Response
    {
        $articleModel = config('northcloud.models.article');

        $query = $articleModel::query()
            ->with(['newsSource', 'tags'])
            ->published();

        if ($search = $request->get('search')) {
            $query->search($search);
        }

        if ($tag = $request->get('tag')) {
            $query->withTag($tag);
        }

        $articles = $query->paginate(15)->withQueryString();

        return Inertia::render('Articles/Index', [
            'articles' => $articles,
            'queryParams' => $request->only(['search', 'tag']),
        ]);
    }

    public function show(string $slug): Response
    {
        $articleModel = config('northcloud.models.article');

        $article = $articleModel::query()
            ->published()
            ->where('slug', $slug)
            ->with(['newsSource', 'tags', 'articleable'])
            ->firstOrFail();

        $article->incrementViewCount();

        return Inertia::render('Articles/Show', [
            'article' => $article,
        ]);
    }
}
```

**Step 2: Create route file**

Create `routes/web/articles.php`:

```php
<?php

use App\Http\Controllers\ArticleController;
use Illuminate\Support\Facades\Route;

Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/articles/{slug}', [ArticleController::class, 'show'])->name('articles.show');
```

**Step 3: Load routes in web.php**

In `routes/web.php`, add after the movies require (before `require __DIR__.'/web/people.php';`):

```php
require __DIR__.'/web/articles.php';
```

**Step 4: Run pint and commit**

```bash
ddev exec vendor/bin/pint --dirty
git add app/Http/Controllers/ArticleController.php routes/web/articles.php routes/web.php
git commit -m "feat: add public article controller and routes"
```

---

### Task 15: Add Article TypeScript type

**Files:**
- Modify: `resources/js/types/models.ts`

**Step 1: Add Article interface**

Add after the existing `Movie` interface:

```typescript
export interface Article {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    content: string | null;
    url: string;
    image_url: string | null;
    author: string | null;
    status: string;
    published_at: string | null;
    view_count: number;
    is_featured: boolean;
    news_source?: {
        id: number;
        name: string;
        slug: string;
    };
    tags?: Tag[];
    articleable_type: string | null;
    articleable_id: number | null;
    articleable?: Movie | null;
    created_at: string;
    updated_at: string;
}

export interface PaginatedArticles {
    data: Article[];
    links: PaginationLinks;
    meta: PaginationMeta;
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}
```

**Step 2: Commit**

```bash
git add resources/js/types/models.ts
git commit -m "feat: add Article TypeScript type"
```

---

### Task 16: Create Articles/Index.vue page

**Files:**
- Create: `resources/js/pages/Articles/Index.vue`

**Step 1: Create the page**

This page follows the same patterns as `Movies/Index.vue` but simplified (no infinite scroll, no view modes — just a clean paginated list).

```vue
<script setup lang="ts">
import type { Article, Tag } from '@/types/models';

import { Head, Link, router } from '@inertiajs/vue3';
import { Clock, ExternalLink, Search as SearchIcon } from 'lucide-vue-next';
import { ref } from 'vue';

import AppSidebarLayout from '@/layouts/AppSidebarLayout.vue';

interface Props {
    articles: {
        data: Article[];
        links: { url: string | null; label: string; active: boolean }[];
        current_page: number;
        last_page: number;
    };
    queryParams: {
        search?: string;
        tag?: string;
    };
}

const props = defineProps<Props>();

const search = ref(props.queryParams.search ?? '');

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const debouncedSearch = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            '/articles',
            { search: search.value || undefined, tag: props.queryParams.tag || undefined },
            { preserveState: true, preserveScroll: true },
        );
    }, 300);
};

const formatDate = (dateStr: string | null): string => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};
</script>

<template>
    <AppSidebarLayout>
        <Head title="Articles" />

        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-white">Articles</h1>
                <p class="mt-2 text-zinc-400">War film analysis, news, and features</p>
            </div>

            <!-- Search -->
            <div class="relative mb-8">
                <SearchIcon class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-zinc-500" />
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search articles..."
                    class="w-full rounded-lg border border-zinc-800 bg-zinc-900 py-2.5 pr-4 pl-10 text-sm text-white placeholder:text-zinc-500 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                    @input="debouncedSearch"
                />
            </div>

            <!-- Articles list -->
            <div v-if="articles.data.length > 0" class="space-y-6">
                <article
                    v-for="article in articles.data"
                    :key="article.id"
                    class="group rounded-xl border border-zinc-800 bg-zinc-900/50 p-6 transition-colors hover:border-zinc-700"
                >
                    <Link :href="`/articles/${article.slug}`" class="block">
                        <h2 class="text-lg font-semibold text-white transition-colors group-hover:text-red-500">
                            {{ article.title }}
                        </h2>

                        <p v-if="article.excerpt" class="mt-2 line-clamp-2 text-sm text-zinc-400">
                            {{ article.excerpt }}
                        </p>

                        <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-zinc-500">
                            <span v-if="article.published_at" class="flex items-center gap-1">
                                <Clock class="size-3" />
                                {{ formatDate(article.published_at) }}
                            </span>
                            <span v-if="article.author">by {{ article.author }}</span>
                            <span v-if="article.news_source" class="text-zinc-600">
                                {{ article.news_source.name }}
                            </span>
                        </div>

                        <div v-if="article.tags && article.tags.length > 0" class="mt-3 flex flex-wrap gap-1">
                            <span
                                v-for="tag in article.tags.slice(0, 4)"
                                :key="tag.id"
                                class="rounded-full bg-zinc-800 px-2 py-0.5 text-xs text-zinc-300"
                            >
                                {{ tag.name }}
                            </span>
                        </div>

                        <div
                            v-if="article.articleable && article.articleable_type?.includes('Movie')"
                            class="mt-3 text-xs text-red-500"
                        >
                            Related to: {{ (article.articleable as any).title }}
                        </div>
                    </Link>
                </article>
            </div>

            <div v-else class="py-16 text-center text-zinc-500">
                <p>No articles found.</p>
            </div>

            <!-- Pagination -->
            <nav v-if="articles.last_page > 1" class="mt-8 flex justify-center gap-1">
                <template v-for="link in articles.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded-lg px-3 py-2 text-sm transition-colors"
                        :class="
                            link.active
                                ? 'bg-red-600 text-white'
                                : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'
                        "
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="rounded-lg px-3 py-2 text-sm text-zinc-600"
                        v-html="link.label"
                    />
                </template>
            </nav>
        </div>
    </AppSidebarLayout>
</template>
```

**Step 2: Commit**

```bash
git add resources/js/pages/Articles/Index.vue
git commit -m "feat: add public Articles/Index.vue page"
```

---

### Task 17: Create Articles/Show.vue page

**Files:**
- Create: `resources/js/pages/Articles/Show.vue`

**Step 1: Create the page**

```vue
<script setup lang="ts">
import type { Article } from '@/types/models';

import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, Eye, ExternalLink, User } from 'lucide-vue-next';

import AppSidebarLayout from '@/layouts/AppSidebarLayout.vue';

interface Props {
    article: Article;
}

const props = defineProps<Props>();

const formatDate = (dateStr: string | null): string => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};
</script>

<template>
    <AppSidebarLayout>
        <Head :title="article.title" />

        <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- Back link -->
            <Link
                href="/articles"
                class="mb-6 inline-flex items-center gap-1 text-sm text-zinc-400 transition-colors hover:text-white"
            >
                <ArrowLeft class="size-4" />
                Back to articles
            </Link>

            <!-- Article header -->
            <header class="mb-8">
                <h1 class="text-3xl font-bold text-white">{{ article.title }}</h1>

                <div class="mt-4 flex flex-wrap items-center gap-4 text-sm text-zinc-400">
                    <span v-if="article.published_at" class="flex items-center gap-1">
                        <Calendar class="size-4" />
                        {{ formatDate(article.published_at) }}
                    </span>
                    <span v-if="article.author" class="flex items-center gap-1">
                        <User class="size-4" />
                        {{ article.author }}
                    </span>
                    <span v-if="article.view_count" class="flex items-center gap-1">
                        <Eye class="size-4" />
                        {{ article.view_count }} views
                    </span>
                </div>

                <!-- Tags -->
                <div v-if="article.tags && article.tags.length > 0" class="mt-4 flex flex-wrap gap-2">
                    <span
                        v-for="tag in article.tags"
                        :key="tag.id"
                        class="rounded-full bg-zinc-800 px-3 py-1 text-xs text-zinc-300"
                    >
                        {{ tag.name }}
                    </span>
                </div>

                <!-- Associated movie -->
                <div
                    v-if="article.articleable && article.articleable_type?.includes('Movie')"
                    class="mt-4 rounded-lg border border-zinc-800 bg-zinc-900/50 p-4"
                >
                    <p class="mb-1 text-xs text-zinc-500">Related movie</p>
                    <Link
                        :href="`/movies/${(article.articleable as any).slug}`"
                        class="text-red-500 transition-colors hover:text-red-400"
                    >
                        {{ (article.articleable as any).title }}
                    </Link>
                </div>
            </header>

            <!-- Featured image -->
            <div v-if="article.image_url" class="mb-8 overflow-hidden rounded-xl">
                <img :src="article.image_url" :alt="article.title" class="w-full object-cover" />
            </div>

            <!-- Article content -->
            <div
                class="prose prose-invert prose-zinc max-w-none prose-headings:text-white prose-p:text-zinc-300 prose-a:text-red-500 prose-a:no-underline hover:prose-a:text-red-400 prose-strong:text-white"
                v-html="article.content"
            />

            <!-- Source link -->
            <div v-if="article.url" class="mt-8 border-t border-zinc-800 pt-6">
                <a
                    :href="article.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-1 text-sm text-zinc-400 transition-colors hover:text-white"
                >
                    <ExternalLink class="size-4" />
                    Read original article
                    <span v-if="article.news_source" class="text-zinc-500">
                        on {{ article.news_source.name }}
                    </span>
                </a>
            </div>
        </div>
    </AppSidebarLayout>
</template>
```

**Step 2: Commit**

```bash
git add resources/js/pages/Articles/Show.vue
git commit -m "feat: add public Articles/Show.vue page"
```

---

### Task 18: Add related articles to Movie show page

**Files:**
- Modify: `app/Http/Controllers/MovieController.php`
- Modify: `resources/js/pages/Movies/Show.vue`

**Step 1: Eager-load articles in MovieController::show()**

In `app/Http/Controllers/MovieController.php`, in the `show()` method, after loading the movie, add:

```php
$relatedArticles = $movie->articles()
    ->published()
    ->with('newsSource')
    ->limit(5)
    ->get();
```

Add to the `Inertia::render` return props:

```php
'relatedArticles' => $relatedArticles,
```

**Step 2: Add related articles section to Movies/Show.vue**

In the `<script setup>` section, add the prop type. In the `Props` interface (or wherever props are defined), add:

```typescript
relatedArticles?: Article[];
```

Import the `Article` type at the top.

In the template, add a "Related Articles" section. Place it after the reviews section (at the end of the content area):

```html
<!-- Related Articles -->
<section
    v-if="relatedArticles && relatedArticles.length > 0"
    class="mt-12 border-t border-zinc-800 pt-8"
>
    <h2 class="mb-6 text-xl font-bold text-white">Related Articles</h2>
    <div class="space-y-4">
        <Link
            v-for="article in relatedArticles"
            :key="article.id"
            :href="`/articles/${article.slug}`"
            class="block rounded-lg border border-zinc-800 bg-zinc-900/50 p-4 transition-colors hover:border-zinc-700"
        >
            <h3 class="font-medium text-white">{{ article.title }}</h3>
            <p v-if="article.excerpt" class="mt-1 line-clamp-2 text-sm text-zinc-400">
                {{ article.excerpt }}
            </p>
            <div class="mt-2 flex items-center gap-2 text-xs text-zinc-500">
                <span v-if="article.published_at">
                    {{ new Date(article.published_at).toLocaleDateString() }}
                </span>
                <span v-if="article.news_source">
                    {{ article.news_source.name }}
                </span>
            </div>
        </Link>
    </div>
</section>
```

**Step 3: Run pint and lint**

```bash
ddev exec vendor/bin/pint --dirty
npm run lint
```

**Step 4: Commit**

```bash
git add app/Http/Controllers/MovieController.php resources/js/pages/Movies/Show.vue
git commit -m "feat: add related articles section to movie detail page"
```

---

### Task 19: Write public articles tests

**Files:**
- Create: `tests/Feature/PublicArticlesTest.php`

**Step 1: Create and write the test**

```bash
ddev artisan make:test PublicArticlesTest --pest --no-interaction
```

Edit `tests/Feature/PublicArticlesTest.php`:

```php
<?php

use App\Models\Movie;
use App\Models\WarArticle;
use JonesRussell\NorthCloud\Models\NewsSource;

test('articles index page shows published articles', function () {
    $source = NewsSource::factory()->create();

    WarArticle::create([
        'news_source_id' => $source->id,
        'title' => 'Published Article',
        'slug' => 'published-article',
        'url' => 'https://example.com/published',
        'content' => 'Content.',
        'status' => 'published',
        'published_at' => now()->subDay(),
    ]);

    WarArticle::create([
        'news_source_id' => $source->id,
        'title' => 'Draft Article',
        'slug' => 'draft-article',
        'url' => 'https://example.com/draft',
        'content' => 'Content.',
        'status' => 'draft',
        'published_at' => null,
    ]);

    $response = $this->get(route('articles.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Articles/Index')
        ->has('articles.data', 1)
        ->where('articles.data.0.title', 'Published Article')
    );
});

test('articles index page supports search', function () {
    $source = NewsSource::factory()->create();

    WarArticle::create([
        'news_source_id' => $source->id,
        'title' => 'Battle of the Bulge Analysis',
        'slug' => 'battle-bulge',
        'url' => 'https://example.com/battle',
        'content' => 'Content.',
        'status' => 'published',
        'published_at' => now(),
    ]);

    WarArticle::create([
        'news_source_id' => $source->id,
        'title' => 'Film Review Roundup',
        'slug' => 'film-review',
        'url' => 'https://example.com/review',
        'content' => 'Content.',
        'status' => 'published',
        'published_at' => now(),
    ]);

    $response = $this->get(route('articles.index', ['search' => 'Bulge']));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('articles.data', 1)
        ->where('articles.data.0.slug', 'battle-bulge')
    );
});

test('article show page loads published article with associated movie', function () {
    $source = NewsSource::factory()->create();
    $movie = Movie::factory()->published()->create(['title' => 'Saving Private Ryan']);

    $article = WarArticle::create([
        'news_source_id' => $source->id,
        'title' => 'Analysis of Saving Private Ryan',
        'slug' => 'analysis-spr',
        'url' => 'https://example.com/spr',
        'content' => '<p>Deep dive content.</p>',
        'status' => 'published',
        'published_at' => now(),
        'articleable_type' => Movie::class,
        'articleable_id' => $movie->id,
    ]);

    $response = $this->get(route('articles.show', 'analysis-spr'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Articles/Show')
        ->where('article.title', 'Analysis of Saving Private Ryan')
        ->where('article.articleable.title', 'Saving Private Ryan')
    );
});

test('article show page returns 404 for draft articles', function () {
    $source = NewsSource::factory()->create();

    WarArticle::create([
        'news_source_id' => $source->id,
        'title' => 'Draft Article',
        'slug' => 'draft-article',
        'url' => 'https://example.com/draft',
        'content' => 'Content.',
        'status' => 'draft',
        'published_at' => null,
    ]);

    $response = $this->get(route('articles.show', 'draft-article'));

    $response->assertNotFound();
});
```

**Step 2: Run the tests**

```bash
ddev artisan test --compact tests/Feature/PublicArticlesTest.php
```

Expected: PASS

**Step 3: Commit**

```bash
git add tests/Feature/PublicArticlesTest.php
git commit -m "test: add public articles index and show page tests"
```

---

### Task 20: Write movie show related articles test

**Files:**
- Modify: `tests/Feature/MovieShowTest.php`

**Step 1: Add test**

Append to `tests/Feature/MovieShowTest.php`:

```php
test('movie show page includes related articles', function () {
    $movie = Movie::factory()->published()->create(['slug' => 'movie-with-articles']);
    $source = \JonesRussell\NorthCloud\Models\NewsSource::factory()->create();

    \App\Models\WarArticle::create([
        'news_source_id' => $source->id,
        'title' => 'Related Article',
        'slug' => 'related-article',
        'url' => 'https://example.com/related',
        'content' => 'Content.',
        'status' => 'published',
        'published_at' => now(),
        'articleable_type' => Movie::class,
        'articleable_id' => $movie->id,
    ]);

    $response = $this->get(route('movies.show', 'movie-with-articles'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Movies/Show')
        ->has('relatedArticles', 1)
        ->where('relatedArticles.0.title', 'Related Article')
    );
});

test('movie show page works when no related articles exist', function () {
    $movie = Movie::factory()->published()->create(['slug' => 'movie-no-articles']);

    $response = $this->get(route('movies.show', 'movie-no-articles'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Movies/Show')
        ->has('relatedArticles', 0)
    );
});
```

**Step 2: Run the tests**

```bash
ddev artisan test --compact tests/Feature/MovieShowTest.php
```

Expected: PASS

**Step 3: Commit**

```bash
git add tests/Feature/MovieShowTest.php
git commit -m "test: add related articles tests for movie show page"
```

---

### Task 21: Build frontend + run full test suite

**Step 1: Build frontend**

```bash
npm run build
```

**Step 2: Run lint**

```bash
npm run lint
```

**Step 3: Run full test suite**

```bash
ddev artisan test --compact
```

Expected: All tests pass.

**Step 4: Generate wayfinder routes (if needed)**

```bash
ddev artisan wayfinder:generate
```

---

## Post-Implementation Checklist

- [ ] All package tests pass (`vendor/bin/pest` in northcloud-laravel)
- [ ] All app tests pass (`ddev artisan test --compact`)
- [ ] Frontend builds cleanly (`npm run build`)
- [ ] Lint passes (`npm run lint`, `ddev exec vendor/bin/pint --dirty`)
- [ ] Admin sidebar shows "NorthCloud" group with Articles + Users
- [ ] Admin can create/edit articles with movie association
- [ ] Public `/articles` page shows published articles
- [ ] Public `/articles/{slug}` shows article detail with movie link
- [ ] Movie detail page shows related articles section
- [ ] Package version is 0.6.0
