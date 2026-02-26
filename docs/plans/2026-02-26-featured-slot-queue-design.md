# Featured Slot Queue & Auto-Publish System

**Date:** 2026-02-26
**Status:** Approved

## Overview

Automated weekly rotation of featured slots (hero + pick_of_week) with a previewable queue, admin overrides, and historical tracking for analytics.

## Requirements

- Both slots rotate every **Sunday at 6:00 AM UTC**
- Fully automatic selection: rating > recency > random tiebreaker
- Never repeat a movie until the full published catalog is exhausted
- Hero and pick_of_week always feature **different movies**
- Admin can override (pin a movie) and preview the upcoming queue
- Track history: movie, slot, dates, and selection method (auto/manual)

## Approach

**Queue Table (Approach A):** Two new tables (`featured_slot_queue`, `featured_slot_history`). The existing `featured_slots` table is unchanged and continues to represent what's live. A scheduled command rotates slots, archives to history, and refills the queue.

## Data Model

### New Table: `featured_slot_queue`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `movie_id` | FK -> movies | cascadeOnDelete |
| `slot` | string | `hero` or `pick_of_week` |
| `position` | integer | Order in queue (1 = next up) |
| `selection_method` | string | `auto` or `manual` |
| `scheduled_for` | date | The Sunday this entry targets |
| `created_at` / `updated_at` | timestamps | |

**Unique constraint:** `(slot, position)`

### New Table: `featured_slot_history`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `movie_id` | FK -> movies | nullOnDelete (preserve history) |
| `slot` | string | `hero` or `pick_of_week` |
| `selection_method` | string | `auto` or `manual` |
| `started_at` | timestamp | When movie went live |
| `ended_at` | nullable timestamp | When replaced (null = current) |
| `created_at` / `updated_at` | timestamps | |

**Index:** `(slot, started_at)`

### Existing Table: `featured_slots` (unchanged)

Stays as-is. Represents what's live right now.

## Selection Algorithm

### Eligibility

1. Movie must have `status = published`
2. Movie must not appear in `featured_slot_history` (unless full catalog exhaustion, then all become eligible again)
3. Movie must not already be in `featured_slot_queue`

### Ranking (deterministic)

- Primary: `tmdb_vote_average DESC` (highest rated first)
- Secondary: `created_at DESC` (most recently added)
- Tertiary: seeded random tiebreaker

### Slot Pairing

Pick top-ranked eligible movie for `pick_of_week` first, then next top-ranked for `hero`. Guarantees they're always different.

### Queue Depth

Auto-generate **4 weeks ahead**. Refill whenever queue drops below 4 entries per slot.

### Catalog Exhaustion

When all published movies have history entries, treat all published movies as eligible again. History records are never deleted.

## Scheduled Rotation: `featured:rotate`

Runs via scheduler every Sunday at 6:00 AM UTC.

### Steps

1. **Archive current slots** — Write `ended_at = now()` on current history rows, create history entries for outgoing movies.
2. **Pop next from queue** — Pull `position = 1` entry from each slot's queue.
3. **Swap in** — Update `featured_slots` with new movies.
4. **Reindex queue** — Delete consumed entries, decrement remaining positions.
5. **Refill queue** — If either slot has fewer than 4 entries, run selection algorithm.
6. **Log** — Create `featured_slot_history` entries with `started_at = now()`, `ended_at = null`.

### Edge Cases

- **Empty queue at rotation**: Run selection on the spot, swap immediately, log as `auto`.
- **No eligible movies**: Keep current slots, log warning.
- **Deleted movie in queue**: Skip, pop next. Remove orphaned entries during refill.

### Scheduler Registration

```php
// routes/console.php
Schedule::command('featured:rotate')->weeklyOn(0, '06:00')->timezone('UTC');
```

## Admin UI

### Queue Preview: `/dashboard/featured-queue`

- Two columns, one per slot type
- Each shows numbered list: position, poster + title, scheduled date, auto/manual badge
- "Refill Queue" button to manually trigger selection algorithm

### Override Actions

- **Insert**: Pick movie + position, created as `manual`, existing entries shift down
- **Remove**: Delete entry, positions reindex
- **Reorder**: Move up/down (or drag-and-drop), moved entries become `manual`
- **Swap slot**: Move entry between hero and pick_of_week queues

### Validation

- Same movie cannot appear in both slot queues for the same Sunday
- Only published movies can be queued

### Existing Pages

- `/dashboard/featured-slots` stays as-is (what's live now)
- Cross-linked with queue page: "Now Showing" vs "Coming Up"

## History: `/dashboard/featured-history`

- Filterable table: Movie, Slot Type, Selection Method, Started At, Ended At, Duration
- Filters: slot type, selection method, date range
- Default sort: most recent first, paginated (20/page)
- Append-only: rows never deleted
- Survives movie deletion: shows "Deleted Movie" for null `movie_id`

## Testing Strategy

### Feature Tests

- **RotateFeaturedSlotsCommand**: Full rotation happy path (archive -> pop -> swap -> refill)
- **Selection algorithm**: Rating > recency ordering, excludes featured movies, catalog exhaustion reset
- **Slot pairing**: Never same movie in both slots for same week
- **Edge cases**: Empty queue, deleted movie in queue, no eligible movies
- **Queue management**: Insert shifts positions, remove reindexes, cross-slot validation

### Admin Controller Tests

- **FeaturedQueueController**: CRUD, reordering, validation
- **FeaturedHistoryController**: Index with filters, sorting, pagination
- **Auth**: All endpoints require admin middleware

### Existing Tests

- HomeTest and DashboardTablesPagingTest unaffected
