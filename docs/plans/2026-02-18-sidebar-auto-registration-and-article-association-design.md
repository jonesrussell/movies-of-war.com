# Sidebar Auto-Registration & Article-Domain Model Association

**Date:** 2026-02-18
**Status:** Approved
**Scope:** northcloud-laravel package (v0.6.0) + movies-of-war app

## Problem

1. Installing northcloud-laravel requires manually editing AppSidebar.vue to add nav links. This friction compounds across consumer sites.
2. Articles have no mechanism to associate with app-specific domain models (Movies in MoW, Recipes in a cooking site, etc.).

## Decisions

- **Sidebar:** Consume existing `Inertia::share('northcloud.navigation')` props. No new published files.
- **Association:** Polymorphic `morphTo` on Article. Config-driven, disabled by default.
- **Form UX:** Searchable dropdown for associatable models, configured per-app.
- **Public display:** Articles index/show pages + related articles on Movie detail.
- **Nav grouping:** Package items render in a separate "NorthCloud" collapsible sidebar group.

---

## Feature 1: Sidebar Auto-Registration

### Architecture

The northcloud-laravel service provider already calls `shareNavigation()`:

```php
Inertia::share('northcloud', fn () => [
    'navigation' => collect(config('northcloud.navigation.items', []))
        ->map(fn (array $item) => [
            'title' => $item['title'],
            'href' => route($item['route']),
            'icon' => $item['icon'],
        ])
        ->all(),
]);
```

Default config declares Articles and Users nav items. The frontend just needs to consume them.

### Icon Resolution

A composable maps icon name strings to Lucide Vue components:

```typescript
// resources/js/composables/useNorthcloudNav.ts
import { FileText, Users, type LucideIcon } from 'lucide-vue-next'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import type { NavItem } from '@/types'

const iconMap: Record<string, LucideIcon> = { FileText, Users }

export function useNorthcloudNav() {
  const page = usePage()
  const items = computed<NavItem[]>(() => {
    const nav = page.props.northcloud?.navigation ?? []
    return nav.map(item => ({
      title: item.title,
      href: item.href,
      icon: iconMap[item.icon],
    }))
  })
  return { items }
}
```

### AppSidebar.vue Changes

- Import `useNorthcloudNav` composable
- Remove hardcoded Users nav item
- Add "NorthCloud" collapsible group rendering `northcloudItems.value`

### Files Changed (App)

| File | Change |
|------|--------|
| `resources/js/composables/useNorthcloudNav.ts` | New composable (~20 lines) |
| `resources/js/components/AppSidebar.vue` | Import composable, replace hardcoded Users with NorthCloud group |

---

## Feature 2: Article Polymorphic Association (Package v0.6.0)

### Migration

```php
// database/migrations/2025_01_01_000005_add_articleable_to_articles_table.php
$table->nullableMorphs('articleable');
```

Nullable: articles can remain standalone.

### Article Model

```php
public function articleable(): MorphTo
{
    return $this->morphTo();
}
```

### Configuration

```php
// config/northcloud.php (package default)
'articleable' => [
    'enabled' => false,
    'models' => [],
],

// config/northcloud.php (consumer app override)
'articleable' => [
    'enabled' => true,
    'models' => [
        App\Models\Movie::class => [
            'label' => 'Movie',
            'display' => 'title',
            'search' => ['title'],
        ],
    ],
],
```

### ArticleResource

When `articleable.enabled` is true, adds an `articleable` field (type: `articleable`, nullable).

### ArticleController

- `create()`/`edit()`: Pass associatable model options to frontend
- `store()`/`update()`: Handle `articleable_type` and `articleable_id`
- New endpoint: `GET /dashboard/articles/search-associatable?model={class}&q={term}`

### ArticleForm.vue

New `articleable` field type: searchable dropdown listing configured models, searches via API, allows clearing.

### Package Files Changed

| File | Change |
|------|--------|
| New migration | `add_articleable_to_articles_table` |
| `src/Models/Article.php` | Add `articleable()` morphTo |
| `config/northcloud.php` | Add `articleable` config section |
| `src/Admin/ArticleResource.php` | Add articleable field when enabled |
| `src/Http/Controllers/Admin/ArticleController.php` | CRUD + search endpoint |
| `resources/js/components/admin/ArticleForm.vue` | Render articleable field type |
| `routes/admin.php` | Add search-associatable route |

---

## Feature 3: MoW App Integration

### Movie Model

```php
public function articles(): MorphMany
{
    return $this->morphMany(
        config('northcloud.models.article', Article::class),
        'articleable'
    );
}
```

### Public Routes

| Route | Page | Description |
|-------|------|-------------|
| `GET /articles` | `Articles/Index.vue` | Paginated published articles, filterable by tag |
| `GET /articles/{slug}` | `Articles/Show.vue` | Article detail with associated movie link |

### Movie Detail

`Movies/Show.vue` gains a "Related Articles" section. Controller eager-loads `$movie->articles()->published()->get()`.

### App Files Changed

| File | Change |
|------|--------|
| `config/northcloud.php` | Add `articleable` config |
| `app/Models/Movie.php` | Add `articles()` morphMany |
| `app/Http/Controllers/ArticleController.php` | New public controller |
| `routes/web/articles.php` | New route file |
| `routes/web.php` | Require articles route file |
| `resources/js/pages/Articles/Index.vue` | Public articles index |
| `resources/js/pages/Articles/Show.vue` | Public article detail |
| `resources/js/pages/Movies/Show.vue` | Related articles section |
| `app/Http/Controllers/MovieController.php` | Eager-load articles for show |

---

## Testing

### Package Tests

| Test | Type | Coverage |
|------|------|----------|
| `ArticleableRelationshipTest` | Feature | morphTo/morphMany, nullable, associate/dissociate |
| `ArticleControllerAssociationTest` | Feature | CRUD with articleable, search endpoint |
| `ArticleResourceFieldsTest` | Unit | Field visibility based on config |

### App Tests

| Test | Type | Coverage |
|------|------|----------|
| `MovieArticlesTest` | Feature | Relationship, eager loading, published scope |
| `PublicArticlesTest` | Feature | Index (published only), show page, associated movie |
| `MovieShowArticlesTest` | Feature | Related articles on movie detail |
| `SidebarNavigationTest` | Feature | NorthCloud nav items for admin, hidden for non-admin |

---

## Implementation Order

1. Package: migration + Article model morphTo + config
2. Package: ArticleResource + ArticleController + form changes
3. Package: search-associatable endpoint
4. Package: tests + version bump to v0.6.0
5. App: sidebar auto-registration (composable + AppSidebar)
6. App: config override + Movie model relationship
7. App: public ArticleController + routes + pages
8. App: Movie detail related articles
9. App: tests
