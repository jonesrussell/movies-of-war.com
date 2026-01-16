# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Movies of War** is a curated database of war films built with Laravel 12, Vue 3, Inertia.js v2, and Tailwind CSS 4. It features TMDB API integration for importing movie metadata, an admin dashboard for content curation, and public browsing with watchlists.

## Development Environment

This project uses **DDEV** for local development. **All commands must be prefixed with `ddev`** when running locally:

```bash
# Examples
ddev artisan migrate
ddev artisan test
ddev composer install
ddev npm install
ddev exec vendor/bin/pint
```

**Important:** Always use `ddev` to run commands. Never run commands directly (e.g., `php artisan`, `composer`, `npm`) - always use `ddev` prefix (e.g., `ddev artisan`, `ddev composer`, `ddev npm`).

### PHP Commands

**All PHP/Artisan commands must be run through DDEV:**

```bash
# Artisan commands
ddev artisan migrate
ddev artisan test
ddev artisan make:migration
ddev artisan make:model
ddev artisan route:list
ddev artisan tinker

# PHP scripts
ddev exec php script.php
ddev exec vendor/bin/pint
ddev exec vendor/bin/phpunit

# Never use direct PHP commands:
# ❌ php artisan migrate
# ❌ php script.php
# ✅ ddev artisan migrate
# ✅ ddev exec php script.php
```

**When running PHP commands in this project, always prefix with `ddev` to ensure they execute in the correct containerized environment.**

## Common Commands

### Development
```bash
# Full development environment (queue, logs, vite - recommended)
ddev composer dev

# Frontend development server only
npm run dev

# Build frontend for production
npm run build

# Lint and format TypeScript/Vue (uses eslint-plugin-prettier)
npm run lint

# Check for lint/format errors without fixing
npm run lint:check

# TypeScript type checking
npm run type-check

# Run both lint and type-check
npm run check
```

### Frontend Testing (Vitest)
```bash
# Run frontend tests once
npm run test

# Run tests in watch mode
npm run test:watch

# Run tests with UI
npm run test:ui

# Run tests with coverage
npm run test:coverage
```

### Backend
```bash
# Run migrations
ddev artisan migrate

# Run all tests
ddev artisan test --compact

# Run specific test file
ddev artisan test --compact tests/Feature/MovieTest.php

# Run tests matching a pattern
ddev artisan test --compact --filter=MovieTest

# Format PHP code
ddev exec vendor/bin/pint
```

### TMDB Movie Import
```bash
# Import war movies from TMDB (creates draft movies)
ddev artisan tmdb:import --limit=50

# Import only upcoming releases
ddev artisan tmdb:import --upcoming --limit=30
```

### User Management
```bash
# Change user password
ddev artisan user:change-password user@example.com

# Grant admin access
ddev artisan user:make-admin user@example.com

# Revoke admin access
ddev artisan user:make-admin user@example.com --revoke
```

### X (Twitter) Post Management

**Queue Worker** (required for publishing):
```bash
# Start the queue worker (required for X post publishing)
ddev artisan queue:work

# Run in background with auto-restart
ddev artisan queue:work --tries=3 --daemon
```

**Scheduler** (required for scheduled posts):
```bash
# The scheduler runs automatically every minute via cron
# Ensure your server has the Laravel scheduler configured:
# For DDEV: * * * * * cd /path-to-project && ddev artisan schedule:run >> /dev/null 2>&1
# For production: * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

**X API Configuration:**
Add these to your `.env` file:
```env
TWITTER_API_KEY=your_api_key
TWITTER_API_SECRET=your_api_secret
TWITTER_ACCESS_TOKEN=your_access_token
TWITTER_ACCESS_TOKEN_SECRET=your_access_token_secret
TWITTER_BEARER_TOKEN=your_bearer_token
```

## Application Architecture

### Movie Status Workflow

Movies have three statuses managed through `App\Models\Movie` constants:
- **draft** - TMDB imports start here, hidden from public
- **published** - Visible on public site, searchable, can be featured
- **archived** - Hidden but preserved, can be restored

**Admin Workflow:**
1. Run `tmdb:import` → movies created as **draft**
2. Review drafts in `/dashboard` (admin only)
3. Click "Publish" to make visible or "Archive" to hide
4. Published movies appear on `/movies` and homepage

### Core Models & Relationships

**Movie** (`app/Models/Movie.php`)
- `belongsToMany(Tag::class)` - Genre, era, theme tags
- `hasMany(FeaturedSlot::class)` - Hero/Pick of Week slots
- `belongsToMany(User::class, 'watchlists')` - User watchlists
- Scopes: `draft()`, `published()`, `archived()`
- Auto-generates slug from title on creation

**FeaturedSlot** (`app/Models/FeaturedSlot.php`)
- `belongsTo(Movie::class)`
- Slots: `hero` (homepage hero section), `pick_of_week`
- Scopes: `active()` (date-based), `slot($type)`

**Tag** (`app/Models/Tag.php`)
- Types: `genre`, `theme`, `era`
- Used for filtering and movie categorization

**User** (`app/Models/User.php`)
- `is_admin` flag controls access to dashboard TMDB section
- `belongsToMany(Movie::class, 'watchlists')` - Personal watchlist

**XPost** (`app/Models/XPost.php`)
- `belongsTo(User::class)` - Post creator/owner
- Status workflow: `draft` → `scheduled` → `published` (or `failed`, `cancelled`)
- Scopes: `draft()`, `scheduled()`, `published()`, `failed()`, `cancelled()`, `readyToPublish()`
- Support for threads (up to 25 tweets) via `thread_parts` JSON field
- Support for media attachments (up to 4 images) via `media_urls` JSON field
- Character limit: 280 characters per tweet (constant: `MAX_TWEET_LENGTH`)
- Helper methods: `canPublish()`, `hasThread()`, `hasMedia()`, `getFullThreadContent()`
- State transitions: `markAsScheduled()`, `markAsPublished()`, `markAsFailed()`, `cancel()`

### TMDB Integration

**Service:** `app/Services/TMDBService.php`
- `discoverWarMovies($page)` - Fetches movies with War genre (ID: 10752)
- `getMovieDetails($tmdbId)` - Full metadata including videos, keywords
- `downloadPoster($path)` - Downloads to `storage/app/public/posters/`
- `getPosterUrl($path)` - Returns TMDB CDN URL

**Import Command:** `app/Console/Commands/ImportTmdbMovies.php`
- Uses `firstOrNew` with `tmdb_id` to avoid duplicates
- New movies: status = `draft`
- Existing movies: preserves current status on re-import
- Auto-tags with genres and detected eras (WWI, WWII, Vietnam)
- Rate limited to 4 requests/second

**Configuration:** `config/tmdb.php`
- Requires `TMDB_API_KEY` in `.env`
- Free API key: https://www.themoviedb.org/settings/api

### X (Twitter) Post Integration

**Status Workflow:**
1. Create **draft** post via admin UI (`/x-posts`)
2. Either:
   - Schedule for future publishing (status → `scheduled`)
   - Publish immediately (dispatches `PublishXPost` job)
3. Scheduler checks every minute for posts ready to publish
4. Job publishes to X API and marks as `published` with `x_post_id`
5. On failure: status → `failed`, `error_message` stored, retries 3 times

**Publishing Job:** `app/Jobs/PublishXPost.php`
- Queued job with 3 retry attempts and exponential backoff (1m, 5m, 15m)
- Handles single tweets and threads (replies chained via `in_reply_to_tweet_id`)
- Supports media upload via X API v1.1 media endpoint
- Posts tweets via X API v2 `/2/tweets` endpoint
- Includes comprehensive error handling and logging

**Scheduler:** Configured in `routes/console.php`
- Runs every minute: finds posts where `status = 'scheduled'` AND `scheduled_for <= now()`
- Dispatches `PublishXPost` job for each ready post
- Uses `withoutOverlapping()` to prevent duplicate executions

**Queue Configuration:**
- Uses database queue driver by default
- Jobs stored in `jobs` table
- Failed jobs stored in `failed_jobs` table with full context
- Run worker: `ddev artisan queue:work --tries=3`

**X API Package:** `atymic/twitter`
- Install: `composer require atymic/twitter`
- Supports both X API v1.1 (media upload) and v2 (post tweets)
- Configure credentials in `.env` (see X API Configuration section above)

**Admin Routes:**
- `/x-posts` - List all posts with status filtering
- `/x-posts/create` - Create new draft or scheduled post
- `/x-posts/{id}/edit` - Edit draft or failed posts only
- `/x-posts/{id}/schedule` - Schedule a draft post
- `/x-posts/{id}/publish` - Publish immediately
- `/x-posts/{id}/cancel` - Cancel a scheduled post

### X API Advanced Features

**X Analytics** (`/dashboard/x-analytics`)
- View performance metrics (impressions, likes, retweets, engagement rate)
- Top performing posts analysis
- Date range filtering
- Manual sync: `POST /x-analytics/sync` - Sync metrics for all published posts
- Scheduled: `x:sync-analytics` runs every 15 minutes

**Trend Monitoring** (`/dashboard/x-trends`)
- Monitor keywords, hashtags, and phrases
- View trending tweets matching monitored keywords
- Manage active/inactive keywords
- Search results stored in `x_trend_results` table
- Scheduled: `x:monitor-trends` runs every 30 minutes

**Auto-Replies** (`/dashboard/x-auto-replies`)
- Configure rules for automatic replies to mentions
- Trigger types: mention, hashtag, keyword
- Priority-based rule matching
- Reply templates with variable substitution
- Scheduled: `x:process-auto-replies` runs every 10 minutes

**Content Discovery** (`/dashboard/x-content-discovery`)
- Discover and curate high-quality war movie content
- Filter by engagement metrics (min likes, retweets)
- Mark posts as featured
- Add notes to curated posts
- Scheduled: `x:discover-content` runs daily at 2 AM

**Auto-Content Generation**
- Generate "War Movie of the Day" posts automatically
- Selects random published movie from database
- Creates draft post with movie details and poster
- Scheduled: `x:generate-content` runs daily at 8 AM

**Website Integration**
- Public API endpoint: `/api/x-feed` - Returns latest 5 published X posts
- XFeedWidget component displays feed on homepage
- Automatic updates via public API

**Console Commands:**
```bash
# Sync analytics for published posts
ddev artisan x:sync-analytics --limit=50

# Monitor all active trend keywords
ddev artisan x:monitor-trends

# Process mentions and send auto-replies
ddev artisan x:process-auto-replies --limit=10

# Discover high-quality content
ddev artisan x:discover-content --min-likes=10 --max-results=50

# Generate "War Movie of the Day" post
ddev artisan x:generate-content
```

**Database Tables:**
- `x_analytics` - Historical tweet performance metrics
- `x_trend_keywords` - Monitored keywords/hashtags
- `x_trend_results` - Trending tweets found for keywords
- `x_auto_reply_rules` - Auto-reply rule configurations
- `x_curated_posts` - Curated content from discovery

**Services:**
- `XApiService` - Core X API wrapper (posting, searching, metrics, mentions)
- `XAnalyticsService` - Analytics sync and reporting
- `XTrendMonitoringService` - Keyword monitoring and trend tracking
- `XAutoReplyService` - Auto-reply processing
- `XContentDiscoveryService` - Content discovery and curation

**Free Tier Limitations:**
- ~500 posts/month
- Limited API read requests
- Some features (real-time filtered stream) require Pro tier
- Rate limiting implemented to respect API limits

### Frontend Structure

**Public Pages** (no auth required):
- `/` - Homepage with hero, pick of week, latest movies grid
- `/movies` - Browse all published movies with filters
- `/movies/{slug}` - Movie detail page with related movies

**Authenticated Pages:**
- `/dashboard` - Stats + TMDB draft management (admins only)
- `/watchlist` - User's saved movies

**Admin-Only Features:**
- `/featured-slots` - Manage hero and pick of week
- `/movies/{id}/publish` - Publish draft movie
- `/movies/{id}/archive` - Archive movie
- TMDB section in dashboard

### Inertia Pages Location

Vue components live in `resources/js/pages/`:
- `Welcome.vue` - Homepage
- `Dashboard.vue` - Unified dashboard (replaces old `/admin`)
- `Movies/Index.vue` - Browse page with search/filters
- `Movies/Show.vue` - Movie detail page
- `Watchlist/Index.vue` - User watchlist
- `Admin/*` - Admin-only management pages

**Shared Components:** `resources/js/components/`
- `MovieCard.vue` - Poster card with hover overlay
- `MovieHero.vue` - Full-width cinematic hero section
- `FeaturedMovie.vue` - Horizontal featured card (pick of week)

### Route Organization

**Public Routes** (`routes/web.php`):
- Movies filtered with `->published()` scope
- Featured slots filtered with `->active()` scope

**Authenticated Routes:**
- Dashboard accessible to all users
- Watchlist CRUD operations

**Admin Routes:**
- Middleware: `['auth', 'verified', 'admin']`
- TMDB publish/archive actions
- Movie and featured slot management

### TypeScript Types

Core types in `resources/js/types/models.ts`:
```typescript
interface Movie {
  id: number
  tmdb_id: number | null
  title: string
  slug: string
  status: 'draft' | 'published' | 'archived'
  release_year: number
  synopsis: string
  poster_url: string | null
  trailer_url: string | null
  tags?: Tag[]
}

interface FeaturedSlot {
  id: number
  movie_id: number
  slot: 'hero' | 'pick_of_week'
  movie?: Movie
}
```

### Authentication & Authorization

- **Laravel Fortify** handles auth (login, register, 2FA, password reset)
- **Admin access** via `is_admin` boolean on User model
- **Middleware:** `admin` alias for `EnsureUserIsAdmin` (checks `is_admin` flag)
- **Views:** Admin-specific UI conditionally rendered with `auth.user?.is_admin`

### Image Assets

**Structure:** `public/images/`
- `branding/` - logo.png, favicon.png, hero-bg.png
- `placeholders/` - poster-placeholder.png
- `illustrations/` - 404.png, no-movies-found.png, watchlist-placeholder.png, etc.

**Downloaded Posters:** `storage/app/public/posters/` (symlinked to `public/storage/posters/`)

### Dark Theme

Uses zinc color palette from Tailwind with red accents:
- Background: `bg-zinc-950`, `bg-zinc-900`
- Text: `text-white`, `text-zinc-400`
- Accents: `bg-red-600`, `text-red-500`

### CSS Architecture & Modern Features

**Main CSS File:** `resources/css/app.css`

#### Tailwind CSS v4 Configuration

The project uses Tailwind CSS v4 with CSS-first configuration via `@theme` directive in `app.css`:

- **Container Variables:** Explicitly defined to prevent Tailwind v4 bug where `max-w-*` utilities incorrectly use `--spacing-*` instead of `--container-*` (see GitHub Discussion #17777)
- **Container Sizes:** All container sizes (`--container-3xs` through `--container-7xl`) are defined in `@theme` block
- **Max-Width Override:** Utilities layer includes `!important` overrides for `.max-w-2xl` and `.max-w-3xl` to ensure correct values (42rem and 48rem respectively)

#### Container Queries

Components use container queries instead of viewport-based breakpoints for better responsiveness:

- **MovieGrid** (`movie-grid` class): Adapts columns based on container width (2 → 3 → 4 → 5 → 6 columns)
- **FeaturedMovie** (`featured-movie` class): Switches from vertical to horizontal layout at `20rem` container width
- **MoviesFiltersPanel** (`filters-panel` class): Responsive filter grid (1 → 2 → 4 columns)
- **StatsGrid** (`stats-grid` class): Dashboard stats grid (1 → 2 → 3 columns)
- **Movie Detail Page** (`movie-detail-grid` class): Two-column layout for movie details

All container queries are defined in `@layer components` in `app.css`.

#### Modern CSS Features

- **Fluid Typography:** Uses `clamp()` for responsive headings (e.g., `font-size: clamp(2rem, 4vw + 1rem, 3.75rem)`)
- **:has() Selector:** Used for form validation styling (`.form-group:has(.error-message)`)
- **Logical Properties:** Uses Tailwind's logical property utilities where appropriate
- **Cascade Layers:** Organized with `@layer base`, `@layer components`, and `@layer utilities`

#### CSS Organization

- **@layer base:** Base styles and compatibility fixes
- **@layer components:** Container queries, form validation, component-specific styles
- **@layer utilities:** Custom utilities, focus rings, font definitions

## Testing Strategy

- **Feature tests** for all user-facing functionality
- **Admin access tests** in `tests/Feature/Admin/AdminAccessTest.php`
- Always use factories for model creation in tests
- Run minimal tests with `--filter` during development

## Important Notes

### Movie Queries
Always use status scopes for public routes:
```php
// Public routes - only published movies
Movie::query()->published()->with('tags')->get();

// Admin routes - show drafts
Movie::query()->draft()->with('tags')->get();
```

### Inertia Shared Props
Access auth data via `usePage()` hook, not via extends:
```typescript
const page = usePage()
const auth = page.props.auth as { user: any }
```

### TMDB Rate Limiting
Import command includes `usleep(250000)` for 4 req/sec limit. Do not increase rate without checking TMDB API limits.

### Migration Order
Featured slots and watchlists depend on movies table. Ensure migration timestamps maintain foreign key order.

