# NorthCloud Audit Remediation Design

**Date:** 2026-02-18
**Scope:** northcloud-laravel 0.7.0 + movies-of-war.com full compliance
**Approach:** Package-first sequential
**Target:** 90%+ compliance (from current 70.7%)

---

## Context

A NorthCloud Platform Standard v1.0 audit of movies-of-war.com scored 70.7% (Needs Attention). The audit identified 12 failures and 10 partial items across package integration and code quality. A critical breaking bug was discovered: the `LinkArticlesToMovies` listener imports a non-existent namespace (`JonesRussell\NorthcloudLaravel`) instead of `JonesRussell\NorthCloud`, so article-to-movie linking is completely broken.

Six package gaps were identified in northcloud-laravel that affect all consumer apps. These are bundled into a 0.7.0 minor release.

## Decisions

- **Versioning:** Single northcloud-laravel 0.7.0 minor release (not incremental patches)
- **Middleware:** movies-of-war.com switches to `northcloud-admin` alias directly (delete local middleware)
- **Navigation:** Both config-based and programmatic `NorthCloud::registerNavigation()` API
- **Linking config:** Package-level `northcloud.linking` section with defaults apps can override
- **Scope:** northcloud-laravel + movies-of-war.com only (other consumer apps audited separately)

---

## Phase 1: northcloud-laravel 0.7.0

### 1A. Config Schema Validation

Add validation in `NorthCloudServiceProvider::boot()`:

- Detect `northcloud.redis.channel` (singular) and warn to use `northcloud.redis.channels` (array)
- Detect `northcloud.processing.processor` (singular) and warn to use `northcloud.processors` (array)
- Detect unknown keys not in the expected schema and warn about invented keys
- Use `Log::warning()` for non-breaking alerts
- Add `northcloud:doctor` artisan command for interactive validation with pass/fail output

### 1B. Navigation Registration API

- Add `NorthCloud` facade with `registerNavigation(array $items)` static method
- Items follow existing schema: `['title' => '...', 'route' => '...', 'icon' => '...']`
- Apps call from `AppServiceProvider::boot()`
- `shareNavigation()` merges config items + programmatically registered items
- Config items render first, then programmatic items in registration order

### 1C. Factory Autoloading

Override `newFactory()` on `Article`, `NewsSource`, and `Tag` models to return the correct factory class explicitly. This bypasses Laravel's namespace-based factory discovery which fails for package models in consumer apps.

### 1D. Linking Config Section

Add to `config/northcloud.php`:

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

### 1E. Strict Types

Add `declare(strict_types=1)` to every PHP file in `src/` and `database/`. Run full test suite to catch type coercion breakage.

### 1F. Install Command Enhancement

Update `northcloud:install` to output a message about the `northcloud-admin` middleware alias being available and how to use it in consumer app routes.

---

## Phase 2: movies-of-war.com Fixes

### 2A. Critical Fixes (Priority 1)

**Namespace bug:**
- `app/Listeners/LinkArticlesToMovies.php:7` — `NorthcloudLaravel` to `NorthCloud`
- `app/Providers/AppServiceProvider.php:19` — same fix

**Config schema alignment** in `config/northcloud.php`:
- `'channel'` to `'channels'` (wrap in array)
- Add root-level `'processors' => [DefaultArticleProcessor::class]`
- Remove `'processing.processor'` key
- Remove `'content.max_excerpt_length'`
- Add `'navigation'` section with app-specific admin items
- Add `'linking'` section overriding package defaults

### 2B. Middleware Cleanup (Priority 2)

- Delete `app/Http/Middleware/EnsureUserIsAdmin.php`
- Remove `'admin'` alias from `bootstrap/app.php`
- Update `routes/web/admin.php`: `Route::middleware('northcloud-admin')`

### 2C. Confidence Thresholds (Priority 2)

Replace hardcoded values in `LinkArticlesToMovies.php` with config reads:
- `config('northcloud.linking.weights.title_match')`
- `config('northcloud.linking.weights.tag_overlap')`
- `config('northcloud.linking.weights.metadata_match')`
- `config('northcloud.linking.threshold')`
- `config('northcloud.linking.min_keyword_length')`

### 2D. Strict Types (Priority 3)

Add `declare(strict_types=1)` to all 25 missing files:
- Models: WarArticle, FeaturedSlot, User
- Middleware: AddCacheHeaders, HandleInertiaRequests, EnsureUserIsAdmin (before deletion), HandleAppearance
- Controllers: WatchlistController, Settings/*
- Requests: ImportTmdbMoviesRequest, Settings/*
- Fortify: CreateNewUser, PasswordValidationRules, ResetUserPassword
- Listeners: LinkArticlesToMovies
- Commands: ChangeUserPassword, MakeUserAdmin, GenerateWarMoviePost
- Providers: FortifyServiceProvider
- Jobs: ImportTmdbMoviesJob

### 2E. Type Safety (Priority 4)

- `FeaturedSlot::scopeActive(Builder $query): Builder` with PHPDoc generics
- `FeaturedSlot::scopeSlot(Builder $query, string $slot): Builder` with PHPDoc generics
- `WarArticle::scopeWarEra(Builder $query, string $era): Builder` with PHPDoc generics
- `LinkArticlesToMovies::extractKeywords(WarArticle $article): array`
- `LinkArticlesToMovies::calculateConfidence(WarArticle $article, Movie $movie, array $keywords): float`

### 2F. Minor Cleanup (Priority 5)

- Extract shared pagination resolver into a trait or helper
- Centralize `usleep(250000)` rate-limit delay into a constant or config
- Register conditional admin nav items via `NorthCloud::registerNavigation()` in `AppServiceProvider::boot()`

### 2G. Version Bump

- `composer.json`: `jonesrussell/northcloud-laravel: ^0.7`

---

## Phase 3: Testing & Verification

### Package Testing

- Config validation: `northcloud:doctor` detects wrong keys, logs warnings
- Navigation API: `NorthCloud::registerNavigation()` merges with config, renders via Inertia
- Factory: `Article::factory()`, `NewsSource::factory()`, `Tag::factory()` work in consumer context
- Linking config: defaults load, overrides work
- Strict types: full suite passes
- Run: `task test`

### Consumer App Testing

- Namespace fix: `LinkArticlesToMovies` fires on `ArticleProcessed` event
- Config: `articles:subscribe` reads correct channel from `northcloud.redis.channels`
- Middleware: admin routes use `northcloud-admin`, 403 for non-admin, 200 for admin
- Strict types: `ddev artisan test --compact` passes
- Scope types: queries still work with Builder type hints
- Frontend: `npm run check` passes
- Run: `ddev artisan test --compact` + `npm run check`

### Final Compliance Re-Audit

Re-run full NorthCloud Platform Standard audit targeting 90%+ score.

---

## Expected Score After Remediation

| Category | Current | Projected |
|----------|:-------:|:---------:|
| A: Package Integration | 66.7% | 96%+ |
| B: Code Quality | 75.0% | 96%+ |
| **Overall** | **70.7%** | **96%+** |
