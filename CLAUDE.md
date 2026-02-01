# CLAUDE.md

## Project Overview

**Movies of War** is a curated database of war films built with Laravel 12, Vue 3, Inertia.js v2, and Tailwind CSS 4. Features TMDB API integration, admin dashboard for content curation, and public browsing with watchlists.

## Development Environment (DDEV)

**All commands must be prefixed with `ddev`:**
```bash
ddev artisan migrate          # NOT: php artisan migrate
ddev artisan test --compact   # NOT: php artisan test
ddev composer install         # NOT: composer install
ddev npm install              # NOT: npm install
ddev exec vendor/bin/pint     # PHP formatting
```

## Common Commands

```bash
# Development
ddev composer dev             # Full dev environment (queue, logs, vite)
npm run dev                   # Frontend dev server
npm run build                 # Production build
npm run lint                  # Lint & format TS/Vue
npm run check                 # Lint + type-check

# Testing
ddev artisan test --compact                              # All tests
ddev artisan test --compact tests/Feature/MovieTest.php  # Specific file
ddev artisan test --compact --filter=MovieTest           # Pattern match
npm run test                                             # Frontend tests

# TMDB Import
ddev artisan tmdb:import --limit=50           # Import war movies (creates drafts)
ddev artisan tmdb:import --upcoming --limit=30

# TMDB Refresh (stale data)
ddev artisan tmdb:refresh-stale --limit=50    # Queue refresh jobs for stale movies
ddev artisan tmdb:refresh-stale --force-backfill --limit=50  # Backfill never-synced
ddev artisan tmdb:refresh-stale --force --limit=10           # Hard refresh (admin)
ddev artisan tmdb:refresh-stale --dry-run                     # List candidates only

# User Management
ddev artisan user:change-password user@example.com
ddev artisan user:make-admin user@example.com [--revoke]
```

## Application Architecture

### Movie Status Workflow

Movies have three statuses (`App\Models\Movie` constants):
- **draft** - TMDB imports start here, hidden from public
- **published** - Visible on public site, searchable, can be featured
- **archived** - Hidden but preserved, can be restored

Admin workflow: `tmdb:import` → drafts in `/dashboard` → Publish/Archive → appears on `/movies`

### Core Models

| Model | Key Relationships & Features |
|-------|------------------------------|
| **Movie** | `belongsToMany(Tag)`, `hasMany(FeaturedSlot)`, `belongsToMany(User, 'watchlists')`. Scopes: `draft()`, `published()`, `archived()`, `staleForTmdbRefresh()`. Auto-generates slug. TMDB-derived: `tmdb_vote_average`, `tmdb_vote_count`, `director`, `writers` (JSON array), `tmdb_last_synced_at`; shown on public movie detail page. |
| **FeaturedSlot** | `belongsTo(Movie)`. Slots: `hero`, `pick_of_week`. Scopes: `active()`, `slot($type)`. |
| **Tag** | Types: `genre`, `theme`, `era`. Used for filtering. |
| **User** | `is_admin` flag for dashboard access. `belongsToMany(Movie, 'watchlists')`. |
| **XPost** | Status: `draft` → `scheduled` → `published` (or `failed`/`cancelled`). Threads via `thread_parts` JSON, media via `media_urls` JSON. 280 char limit. |

### TMDB Integration

- **Service:** `app/Services/TMDBService.php` - `discoverWarMovies()`, `getMovieDetails()` (requests `videos`, `keywords`, `credits`), `downloadPoster()`
- **DTO:** `app/Data/Tmdb/TmdbMovieData.php` - parses credits into `director` and `writers`; `toMovieAttributes()` includes vote average/count and credits for import
- **Import:** `app/Console/Commands/ImportTmdbMovies.php` - uses DTO and `toMovieAttributes()`, rate limited to 4 req/sec. Dashboard single-import (`DashboardController::importSingleTmdbMovie`) also uses DTO and shared slug/tag logic.
- **Config:** `config/tmdb.php` - requires `TMDB_API_KEY` in `.env`; also defines `refresh_cadence_days`, `refresh_popular_vote_count`, `refresh_obscure_vote_count`, `refresh_max_age_days` for stale-refresh cadence.

**TMDB refresh (stale data):**
- **Column:** `movies.tmdb_last_synced_at` – set on import/refresh; used for cadence-based staleness.
- **Job:** `app/Jobs/RefreshTmdbMovieJob.php` – one movie per job, queue `tmdb-refresh`; updates from DTO (no poster re-download, no slug change).
- **Command:** `tmdb:refresh-stale` – `--limit`, `--force-backfill` (null timestamps), `--force` (ignore cadence), `--dry-run`. Dispatches jobs for stale movies (cadence + max-age cap).
- **Scheduler:** `routes/console.php` – `tmdb:refresh-stale --limit=50` runs `everyFifteenMinutes()->between('03:00', '05:00')`.
- **Scope:** `Movie::staleForTmdbRefresh($limit, $maxAgeDays)` – cadence by `tmdb_vote_count` (popular 7d, normal 30d, obscure 90d) plus max-age cap (default 120d).

### X (Twitter) Integration

**Status Workflow:** Create draft → Schedule or publish immediately → Job publishes via X API v2

**Key Components:**
- `app/Jobs/PublishXPost.php` - Queued with 3 retries, handles threads and media
- Scheduler in `routes/console.php` - Checks every minute for scheduled posts
- Package: `atymic/twitter` (v1.1 for media, v2 for tweets)

**Queue:** `ddev artisan queue:work --tries=3`

**X API Features:**
- Analytics: `/dashboard/x-analytics` - `x:sync-analytics` every 15 min
- Trends: `/dashboard/x-trends` - `x:monitor-trends` every 30 min
- Auto-replies: `/dashboard/x-auto-replies` - `x:process-auto-replies` every 10 min
- Content discovery: `/dashboard/x-content-discovery` - `x:discover-content` daily 2 AM
- Auto-content: `x:generate-content` daily 8 AM

**Env vars:** `TWITTER_API_KEY`, `TWITTER_API_SECRET`, `TWITTER_ACCESS_TOKEN`, `TWITTER_ACCESS_TOKEN_SECRET`, `TWITTER_BEARER_TOKEN`

### Frontend Structure

**Pages** (`resources/js/pages/`):
- Public: `Welcome.vue` (homepage), `Movies/Index.vue` (browse), `Movies/Show.vue` (detail)
- Auth: `Dashboard.vue` (stats + admin), `Watchlist/Index.vue`
- Admin: `Admin/*` management pages

**Components** (`resources/js/components/`): `MovieCard.vue`, `MovieHero.vue`, `FeaturedMovie.vue`, `public/MovieFacts.vue` (release year, runtime, country, conflict, TMDB rating, director, writers)

**Types** (`resources/js/types/models.ts`):
```typescript
interface Movie {
  id: number; tmdb_id: number | null; title: string; slug: string;
  status: 'draft' | 'published' | 'archived'; release_year: number;
  synopsis: string; poster_url: string | null; trailer_url: string | null;
  tmdb_vote_average?: number | null; director?: string | null; writers?: string[] | null;
  tags?: Tag[];
}
```

### Routes

- **Entry:** `routes/web.php` – requires `routes/web/*.php` (home, movies, dashboard, watchlist, admin, misc) and `routes/settings.php`. Auth group `['auth', 'verified']` wraps dashboard, watchlist, admin; public movie routes loaded after admin to avoid slug/resource conflicts.
- **Web split:** `routes/web/home.php` (Welcome), `routes/web/movies.php` (public index/show), `routes/web/dashboard.php`, `routes/web/watchlist.php`, `routes/web/admin.php` (admin middleware + TMDB, movies, featured slots, X), `routes/web/misc.php` (api/x-feed, /admin redirect).
- **Public:** Movies filtered with `->published()`, featured slots with `->active()`
- **Auth:** Dashboard, watchlist CRUD
- **Admin:** Middleware `['auth', 'verified', 'admin']` - publish/archive, featured slots

### Auth

- **Fortify** handles auth (login, register, 2FA, password reset)
- **Admin:** `is_admin` boolean on User, `admin` middleware alias for `EnsureUserIsAdmin`

### CSS Architecture

**Tailwind CSS v4** with CSS-first config via `@theme` in `resources/css/app.css`:
- Container queries for responsive components (`movie-grid`, `featured-movie`, `filters-panel`, `stats-grid`)
- Fluid typography with `clamp()`
- Dark theme: zinc palette with red accents (`bg-zinc-950`, `text-red-500`)

**Note:** Container variables explicitly defined to prevent Tailwind v4 bug with `max-w-*` utilities (GitHub #17777)

### Image Assets

- Branding: `public/images/branding/`
- Placeholders: `public/images/placeholders/`
- Posters: `storage/app/public/posters/` (symlinked to `public/storage/posters/`)

## Important Notes

**Always use status scopes for public routes:**
```php
Movie::query()->published()->with('tags')->get();  // Public
Movie::query()->draft()->with('tags')->get();      // Admin
```

**Inertia auth access:**
```typescript
const page = usePage()
const auth = page.props.auth as { user: any }
```

**TMDB rate limiting:** `usleep(250000)` for 4 req/sec limit - do not increase.

**Migration order:** Featured slots and watchlists depend on movies table.

**Wayfinder / lint:** Wayfinder-generated files under `resources/js/actions/**`, `resources/js/routes/**`, `resources/js/wayfinder/**` are excluded from ESLint and Prettier (see `eslint.config.js` ignores and `.prettierignore`). Do not manually edit those files; run `php artisan wayfinder:generate` after route changes.
