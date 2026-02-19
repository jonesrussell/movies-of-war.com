# Public Pages Redesign Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Redesign all public pages with a "Refined Intelligence" aesthetic — Slate Command palette, JetBrains Mono + IBM Plex Sans typography, coordinate grid textures, sharp borders.

**Architecture:** Full design system rebuild. Update CSS tokens and fonts first, then cascade through shared components (header, footer, section headers, cards, grids), then redesign each page. The dashboard/admin pages are NOT affected — only the public-facing pages using `PublicLayout`.

**Tech Stack:** Vue 3, Tailwind CSS v4, Inertia.js v2, shadcn-vue, Lucide icons, Bunny Fonts (JetBrains Mono, IBM Plex Sans)

**Verification:** After each task: `npm run lint` + `npm run build` + visual check. Existing Vitest tests must still pass: `npm run test`.

---

## Phase 1: Design System Foundation

### Task 1: Update fonts in Blade layout and CSS theme

**Files:**
- Modify: `resources/views/app.blade.php:55-57` (font links)
- Modify: `resources/css/app.css:11-19` (font-sans, font-reading theme vars)
- Modify: `resources/css/app.css:418-425` (body font-sans override)

**Step 1: Update Bunny Fonts link**

In `resources/views/app.blade.php`, replace the Instrument Sans font links (lines 55-57) with JetBrains Mono + IBM Plex Sans:

```html
<link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
<link href="https://fonts.bunny.net/css?family=jetbrains-mono:400,500,600,700|ibm-plex-sans:400,500,600&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
<noscript><link href="https://fonts.bunny.net/css?family=jetbrains-mono:400,500,600,700|ibm-plex-sans:400,500,600&display=swap" rel="stylesheet"></noscript>
```

Also update the theme-color meta tag (line 53) from `#18181b` (zinc) to `#0c1222` (navy black):
```html
<meta name="theme-color" content="#0c1222">
```

**Step 2: Update CSS @theme font variables**

In `resources/css/app.css`, update the `@theme inline` block:

Replace `--font-sans` (lines 12-15):
```css
--font-sans:
    'IBM Plex Sans', ui-sans-serif, system-ui, sans-serif,
    'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol',
    'Noto Color Emoji';
```

Replace `--font-reading` (lines 17-19):
```css
/* Monospace display font for headers, data labels, kickers */
--font-mono-display:
    'JetBrains Mono', ui-monospace, 'Cascadia Code', 'Source Code Pro', monospace;
```

Note: Keep `--font-reading` for backward compat with `.review-content`, but also add the new `--font-mono-display`.

**Step 3: Update the body font override in @layer utilities**

In `resources/css/app.css`, update lines 418-425:
```css
body,
html {
    --font-sans:
        'IBM Plex Sans', ui-sans-serif, system-ui, sans-serif,
        'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol',
        'Noto Color Emoji';
}
```

**Step 4: Run lint and build**

Run: `npm run lint && npm run build`
Expected: PASS (font changes are CSS-only, no component breakage)

**Step 5: Commit**

```bash
git add resources/views/app.blade.php resources/css/app.css
git commit -m "style: switch fonts to JetBrains Mono + IBM Plex Sans for intelligence aesthetic"
```

---

### Task 2: Update CSS color tokens for Slate Command palette

**Files:**
- Modify: `resources/css/app.css:466-501` (:root light tokens)
- Modify: `resources/css/app.css:503-537` (.dark tokens)
- Modify: `resources/css/app.css:36-43` (inline HTML background styles in app.blade.php)
- Modify: `resources/css/app.css:429` (focus-ring utility)

**Step 1: Add intelligence-specific custom properties to @theme**

Add these new tokens inside the existing `@theme inline` block (after the easing functions, around line 127):

```css
/* Intelligence palette — Slate Command */
--intel-bg-base: #0c1222;
--intel-bg-surface: #131c31;
--intel-bg-elevated: #1a2540;
--intel-border: #1e3050;
--intel-border-bright: #2a4060;
--intel-accent: #3b82f6;
--intel-accent-dim: #2563eb;
--intel-accent-glow: rgba(59, 130, 246, 0.15);
--intel-alert: #ef4444;
--intel-text-primary: #e2e8f0;
--intel-text-body: #94a3b8;
--intel-text-muted: #64748b;
--intel-text-faint: #475569;
```

**Step 2: Update .dark CSS custom properties**

Update the `.dark` block (lines 503-537) to use Slate Command values for the shadcn token bridge:

```css
.dark {
    --background: #0c1222;
    --foreground: #e2e8f0;
    --card: #131c31;
    --card-foreground: #e2e8f0;
    --popover: #131c31;
    --popover-foreground: #e2e8f0;
    --primary: #3b82f6;
    --primary-foreground: #ffffff;
    --secondary: #1a2540;
    --secondary-foreground: #e2e8f0;
    --muted: #1a2540;
    --muted-foreground: #64748b;
    --accent: #1a2540;
    --accent-foreground: #e2e8f0;
    --destructive: #ef4444;
    --destructive-foreground: #ffffff;
    --border: #1e3050;
    --input: #1e3050;
    --ring: #3b82f6;
    --chart-1: #3b82f6;
    --chart-2: #22d3ee;
    --chart-3: #f59e0b;
    --chart-4: #8b5cf6;
    --chart-5: #ef4444;
    --sidebar-background: #0a0f1a;
    --sidebar-foreground: #e2e8f0;
    --sidebar-primary: #3b82f6;
    --sidebar-primary-foreground: #ffffff;
    --sidebar-accent: #1a2540;
    --sidebar-accent-foreground: #e2e8f0;
    --sidebar-border: #1e3050;
    --sidebar-ring: #3b82f6;
    --sidebar: #0a0f1a;
}
```

**Step 3: Update Blade inline background style**

In `resources/views/app.blade.php`, update the inline `<style>` block (lines 35-43):

```html
<style>
    html {
        background-color: #f8fafc;
    }
    html.dark {
        background-color: #0c1222;
    }
</style>
```

**Step 4: Update focus-ring utility**

In `resources/css/app.css`, update the `.focus-ring` class (line 429) to use blue instead of red:

```css
.focus-ring {
    @apply outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-[#0c1222];
}
```

**Step 5: Run lint and build**

Run: `npm run lint && npm run build`
Expected: PASS

**Step 6: Commit**

```bash
git add resources/css/app.css resources/views/app.blade.php
git commit -m "style: update color tokens to Slate Command intelligence palette"
```

---

### Task 3: Update border radii and add new animations

**Files:**
- Modify: `resources/css/app.css:115-122` (radius tokens)
- Modify: `resources/css/app.css:373-402` (keyframe animations)
- Modify: `resources/css/app.css:491` (--radius base value)

**Step 1: Update base radius**

In `:root` (line 491), change:
```css
--radius: 0.25rem;
```

This makes `rounded-md` the new default (sharp, precise edges).

**Step 2: Add new keyframe animations**

After the existing `movie-card-fade` animation (line 383), add:

```css
/* Intelligence data-in: slide from left */
@keyframes data-slide-in {
    from {
        opacity: 0;
        transform: translateX(-12px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.data-enter {
    animation: data-slide-in 0.5s var(--ease-smooth-out) both;
    animation-delay: var(--delay, 0ms);
}

/* Status pulse for live indicators */
@keyframes status-pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.status-live {
    animation: status-pulse 2s ease-in-out infinite;
}

/* Scan line glow divider */
@keyframes scan-glow {
    0%, 100% {
        opacity: 0.4;
    }
    50% {
        opacity: 0.8;
    }
}
```

**Step 3: Update reduced-motion block**

Add the new animation classes to the reduced-motion block (after line 462):
```css
.data-enter {
    animation: none !important;
}
.status-live {
    animation: none !important;
}
```

**Step 4: Run lint and build**

Run: `npm run lint && npm run build`
Expected: PASS

**Step 5: Commit**

```bash
git add resources/css/app.css
git commit -m "style: sharpen border radii and add intelligence animation keyframes"
```

---

### Task 4: Create CoordinateGrid primitive (replace DotPattern)

**Files:**
- Create: `resources/js/components/primitives/CoordinateGrid.vue`
- Modify: `resources/js/components/primitives/DotPattern.vue` (keep for backward compat, re-export)

**Step 1: Create CoordinateGrid component**

Create `resources/js/components/primitives/CoordinateGrid.vue`:

```vue
<script setup lang="ts">
import { computed } from 'vue';

import { cn } from '@/lib/utils';

type GridDensity = 'sparse' | 'normal' | 'dense';

interface Props {
    density?: GridDensity;
    opacity?: number;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    density: 'normal',
    opacity: 0.04,
    class: '',
});

const gridSize = computed(
    () =>
        ({
            sparse: 60,
            normal: 40,
            dense: 24,
        })[props.density],
);
</script>

<template>
    <div
        :class="cn('pointer-events-none absolute inset-0', $props.class)"
        :style="{
            backgroundImage: `
                linear-gradient(to right, rgba(59, 130, 246, ${opacity}) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(59, 130, 246, ${opacity}) 1px, transparent 1px)
            `,
            backgroundSize: `${gridSize}px ${gridSize}px`,
        }"
    />
</template>
```

**Step 2: Run lint and build**

Run: `npm run lint && npm run build`
Expected: PASS

**Step 3: Commit**

```bash
git add resources/js/components/primitives/CoordinateGrid.vue
git commit -m "feat: add CoordinateGrid primitive for intelligence texture"
```

---

### Task 5: Create ScanLine divider component

**Files:**
- Create: `resources/js/components/primitives/ScanLine.vue`

**Step 1: Create ScanLine component**

Create `resources/js/components/primitives/ScanLine.vue`:

```vue
<script setup lang="ts">
import { cn } from '@/lib/utils';

interface Props {
    class?: string;
    animated?: boolean;
}

withDefaults(defineProps<Props>(), {
    class: '',
    animated: false,
});
</script>

<template>
    <div
        :class="
            cn(
                'h-px w-full',
                'bg-gradient-to-r from-transparent via-blue-500/40 to-transparent',
                animated && 'animate-[scan-glow_3s_ease-in-out_infinite]',
                $props.class,
            )
        "
    />
</template>
```

**Step 2: Run lint and build**

Run: `npm run lint && npm run build`
Expected: PASS

**Step 3: Commit**

```bash
git add resources/js/components/primitives/ScanLine.vue
git commit -m "feat: add ScanLine divider primitive for intelligence aesthetic"
```

---

## Phase 2: Shared Components

### Task 6: Redesign SectionHeader

**Files:**
- Modify: `resources/js/components/public/SectionHeader.vue`

**Step 1: Update SectionHeader with intelligence styling**

Read the current file first, then update the template to use:
- Kicker: `font-[family-name:var(--font-mono-display)]`, uppercase, tracking-[0.2em], text-blue-500, with a horizontal line prefix (`--- BROWSE` pattern)
- Title: `font-[family-name:var(--font-mono-display)]`, text-[--intel-text-primary]
- Description: text-[--intel-text-body]

The kicker should render as: `<span class="inline-block w-6 h-px bg-blue-500 align-middle mr-3"></span>BROWSE`

**Step 2: Run lint and build**

Run: `npm run lint && npm run build`
Expected: PASS

**Step 3: Commit**

```bash
git add resources/js/components/public/SectionHeader.vue
git commit -m "style: redesign SectionHeader with intelligence aesthetic"
```

---

### Task 7: Redesign PublicHeader

**Files:**
- Modify: `resources/js/components/public/PublicHeader.vue`
- Modify: `resources/js/components/public/PublicNav.vue`
- Modify: `resources/js/components/public/PublicMobileMenu.vue`

**Step 1: Update PublicHeader**

Replace zinc colors with intelligence palette:
- Background: `bg-[--intel-bg-base]/80 backdrop-blur-md`
- Border: `border-[--intel-border]`
- Remove scroll-based padding animation (just use consistent `py-3`)
- Active link indicator: blue underline via `border-b-2 border-blue-500`

**Step 2: Update PublicNav**

- Font: IBM Plex Sans (already the body font after Task 1)
- Add `tracking-wide` to nav links
- Active state: `text-white border-b-2 border-blue-500`
- Hover: `text-[--intel-text-primary]`
- Auth links: "WATCHLIST" label with monospace count badge

**Step 3: Update PublicMobileMenu**

- Background: `bg-[--intel-bg-surface]`
- Border: `border-[--intel-border]`
- Links stacked with `border-b border-[--intel-border]` dividers

**Step 4: Run lint and build**

Run: `npm run lint && npm run build`
Expected: PASS

**Step 5: Commit**

```bash
git add resources/js/components/public/PublicHeader.vue resources/js/components/public/PublicNav.vue resources/js/components/public/PublicMobileMenu.vue
git commit -m "style: redesign PublicHeader with intelligence nav styling"
```

---

### Task 8: Redesign PublicFooter

**Files:**
- Modify: `resources/js/components/public/PublicFooter.vue`

**Step 1: Expand footer to three-column layout**

Replace the minimal current footer with:
- Three-column grid (stacked on mobile): Site info | Quick links | Intel meta
- Site info: "MOVIES OF WAR" in monospace, brief description in body text
- Quick links: Browse, Articles, Watchlist as nav links
- Intel meta: TMDB attribution, "DATA PROVIDED BY TMDB" in small monospace
- Bottom bar: full-width, `border-t border-[--intel-border]`, copyright in monospace + `MOW-2026 // OPERATIONAL`
- Coordinate grid texture at low opacity in footer background

**Step 2: Run lint and build**

Run: `npm run lint && npm run build`
Expected: PASS

**Step 3: Commit**

```bash
git add resources/js/components/public/PublicFooter.vue
git commit -m "style: redesign PublicFooter with three-column intelligence layout"
```

---

### Task 9: Redesign MovieCard

**Files:**
- Modify: `resources/js/components/MovieCard.vue`

**Step 1: Read current MovieCard**

Read the file to understand current structure and props.

**Step 2: Update MovieCard styling**

- Border: `rounded-md border border-[--intel-border]`
- Background: `bg-[--intel-bg-surface]`
- Hover: `border-[--intel-accent] shadow-[0_0_12px_var(--intel-accent-glow)]` (blue glow)
- Poster: `rounded-sm` (sharper)
- Title: keep as-is but ensure `text-[--intel-text-primary]`
- Tags: `font-[family-name:var(--font-mono-display)] text-xs` as small monospace badges
- Rating: monospace digits (`font-[family-name:var(--font-mono-display)]`)
- Remove any red accent colors, replace with blue

**Step 3: Run lint, build, and tests**

Run: `npm run lint && npm run build && npm run test`
Expected: PASS (MovieCard.test.ts should still pass — tests check structure, not colors)

**Step 4: Commit**

```bash
git add resources/js/components/MovieCard.vue
git commit -m "style: redesign MovieCard with intelligence card treatment"
```

---

### Task 10: Redesign MovieGrid

**Files:**
- Modify: `resources/js/components/public/MovieGrid.vue`
- Modify: `resources/css/app.css` (movie-grid container query section)

**Step 1: Update MovieGrid component**

- Add `gap-px bg-[--intel-border]` to create visible grid lines between cards (each card needs `bg-[--intel-bg-base]` to show through the gap)
- Alternative: use `gap-4` with visible grid if gap-px creates visual issues

**Step 2: Update movie-card-fade animation in app.css**

Change `translateY(6px)` to `translateX(-8px)` for the data-in direction:

```css
@keyframes movie-card-fade {
    from {
        opacity: 0;
        transform: translateX(-8px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}
```

**Step 3: Run lint and build**

Run: `npm run lint && npm run build`
Expected: PASS

**Step 4: Commit**

```bash
git add resources/js/components/public/MovieGrid.vue resources/css/app.css
git commit -m "style: redesign MovieGrid with intelligence grid treatment"
```

---

### Task 11: Redesign FilterChip and MoviesFiltersPanel

**Files:**
- Modify: `resources/js/components/public/FilterChip.vue`
- Modify: `resources/js/components/public/MoviesFiltersPanel.vue`

**Step 1: Update FilterChip**

- Border: `border border-[--intel-border] rounded-md`
- Background: `bg-[--intel-bg-surface]`
- Text: monospace font for the label
- Remove button: `text-[--intel-text-muted] hover:text-[--intel-alert]`

**Step 2: Update MoviesFiltersPanel — replace raw `<select>` with shadcn Select**

Read the current file to understand the filter options and v-model bindings.

Replace each `<select>` with shadcn `Select` / `SelectTrigger` / `SelectContent` / `SelectItem`:

```vue
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
```

Each filter dropdown becomes:
```vue
<Select v-model="localFilters.conflict">
    <SelectTrigger class="border-[--intel-border] bg-[--intel-bg-surface]">
        <SelectValue placeholder="All Conflicts" />
    </SelectTrigger>
    <SelectContent>
        <SelectItem value="">All Conflicts</SelectItem>
        <SelectItem v-for="c in conflicts" :key="c" :value="c">{{ c }}</SelectItem>
    </SelectContent>
</Select>
```

**Step 3: Run lint and build**

Run: `npm run lint && npm run build`
Expected: PASS

**Step 4: Commit**

```bash
git add resources/js/components/public/FilterChip.vue resources/js/components/public/MoviesFiltersPanel.vue
git commit -m "style: redesign filters with shadcn Select and intelligence styling"
```

---

### Task 12: Redesign MovieListItem

**Files:**
- Modify: `resources/js/components/public/MovieListItem.vue`

**Step 1: Update MovieListItem styling**

- Border: `border-b border-[--intel-border]`
- Metadata (year, runtime, rating): `font-[family-name:var(--font-mono-display)] text-xs text-[--intel-text-muted]` inline
- Title: `text-[--intel-text-primary]`
- More data-dense: show conflict, country, year, runtime all on one metadata line
- Hover: `bg-[--intel-bg-elevated]`

**Step 2: Run lint and build**

Run: `npm run lint && npm run build`
Expected: PASS

**Step 3: Commit**

```bash
git add resources/js/components/public/MovieListItem.vue
git commit -m "style: redesign MovieListItem with data-dense intelligence rows"
```

---

### Task 13: Redesign MovieFacts

**Files:**
- Modify: `resources/js/components/public/MovieFacts.vue`

**Step 1: Update MovieFacts**

- Values: `font-[family-name:var(--font-mono-display)]` for all data values
- Labels: small, `text-[--intel-text-muted]`, uppercase
- Thin dividers between entries: `border-b border-[--intel-border]`
- Fix duplicate icon: use `Swords` icon for "Conflict" field, `Clapperboard` for "Production Status" (currently both use `Film`)
- Replace `Film` icon import with `Swords` and `Clapperboard` from lucide-vue-next

**Step 2: Run lint and build**

Run: `npm run lint && npm run build`
Expected: PASS

**Step 3: Commit**

```bash
git add resources/js/components/public/MovieFacts.vue
git commit -m "style: redesign MovieFacts with monospace data grid and fix duplicate icons"
```

---

## Phase 3: Hero Components

### Task 14: Redesign MovieHero

**Files:**
- Modify: `resources/js/components/MovieHero.vue`

**Step 1: Read current MovieHero**

Read the file to understand its full structure.

**Step 2: Update MovieHero**

- Replace DotPattern with CoordinateGrid
- Kicker: `PRIORITY BRIEFING // PICK OF THE WEEK` in `font-[family-name:var(--font-mono-display)]` blue
- Title: `font-[family-name:var(--font-mono-display)]` with fluid clamp() sizing
- Replace red accents with blue (`text-red-500` -> `text-blue-500`, `bg-red-600` -> `bg-blue-600`)
- CTA buttons: "VIEW DOSSIER" (primary blue), "ADD TO WATCHLIST" (outlined)
- Background overlays: keep blurred poster, replace zinc gradients with navy gradients (`from-[--intel-bg-base]`)
- Stagger animation: use `data-enter` class (translateX) instead of translateY

**Step 3: Run lint, build, and tests**

Run: `npm run lint && npm run build && npm run test`
Expected: PASS (MovieHero.test.ts should still pass)

**Step 4: Commit**

```bash
git add resources/js/components/MovieHero.vue
git commit -m "style: redesign MovieHero with intelligence briefing aesthetic"
```

---

### Task 15: Redesign FeaturedMovie

**Files:**
- Modify: `resources/js/components/FeaturedMovie.vue`

**Step 1: Read current FeaturedMovie**

Read the file to understand its structure.

**Step 2: Update FeaturedMovie**

- Border: `rounded-md border border-[--intel-border]`
- Background: `bg-[--intel-bg-surface]`
- Kicker: `INCOMING // FEATURED RELEASE` in monospace blue
- Release date: if available, display as countdown-style `T-XX DAYS` in monospace
- Replace red accents with blue
- Poster: `rounded-sm`

**Step 3: Run lint, build, and tests**

Run: `npm run lint && npm run build && npm run test`
Expected: PASS (FeaturedMovie.test.ts should still pass)

**Step 4: Commit**

```bash
git add resources/js/components/FeaturedMovie.vue
git commit -m "style: redesign FeaturedMovie with incoming release intelligence card"
```

---

## Phase 4: Page Redesigns

### Task 16: Redesign Welcome page

**Files:**
- Modify: `resources/js/pages/Welcome.vue`

**Step 1: Read current Welcome.vue**

Read the file to understand its full structure.

**Step 2: Update Welcome.vue**

- Replace all zinc/red color classes with intelligence palette equivalents
- Replace DotPattern usages with CoordinateGrid
- Remove XFeedWidget section (or replace with a ScanLine divider)
- Remove the bottom CTA banner
- Add "Intel Summary" section at the bottom: horizontal row of data points (total films, genres, decades) in monospace, with a ScanLine above it
- Update fallback hero (no featured movie) with intelligence-styled text: "AWAITING PRIORITY BRIEFING" or similar
- Ensure all section backgrounds use `bg-[--intel-bg-base]`

**Step 3: Run lint, build, and tests**

Run: `npm run lint && npm run build && npm run test`
Expected: PASS (Welcome.test.ts should still pass)

**Step 4: Commit**

```bash
git add resources/js/pages/Welcome.vue
git commit -m "style: redesign Welcome page with intelligence command center layout"
```

---

### Task 17: Redesign Movies/Index page

**Files:**
- Modify: `resources/js/pages/Movies/Index.vue`

**Step 1: Read current Movies/Index.vue**

Read the file to understand its full structure.

**Step 2: Update Movies/Index.vue**

- Replace all zinc/red classes with intelligence palette
- Replace DotPattern with CoordinateGrid
- Quick filter pills: `font-[family-name:var(--font-mono-display)]`, `border border-[--intel-border] rounded-md`, active: `bg-blue-600 text-white`
- Controls bar: `bg-[--intel-bg-surface] border border-[--intel-border] rounded-md`
- Search input: `placeholder="SEARCH DATABASE..."` in monospace
- Add data header above results: `SHOWING X-Y OF Z // SORTED BY: [SORT]` in small monospace
- Pagination: monospace page numbers, active: `bg-blue-600 text-white`
- Film count in subtitle: `247 ENTRIES INDEXED` in monospace
- Infinite scroll toggle: restyle checkbox
- Empty state: update colors and tone

**Step 3: Run lint, build, and tests**

Run: `npm run lint && npm run build && npm run test`
Expected: PASS (Index.test.ts should still pass)

**Step 4: Commit**

```bash
git add resources/js/pages/Movies/Index.vue
git commit -m "style: redesign Movies/Index with intelligence database browser"
```

---

### Task 18: Redesign Movies/Show page

**Files:**
- Modify: `resources/js/pages/Movies/Show.vue`

**Step 1: Read current Movies/Show.vue**

Read the file to understand its full structure.

**Step 2: Update Movies/Show.vue**

- Replace all zinc/red classes with intelligence palette
- Replace DotPattern with CoordinateGrid
- Background gradients: `from-[--intel-bg-base]` instead of `from-zinc-950`
- Back link: `< BACK TO INDEX` in `font-[family-name:var(--font-mono-display)]`
- Poster: `rounded-md border border-[--intel-border]`, hover blue glow
- Title: `font-[family-name:var(--font-mono-display)]`
- Tags: monospace badges with `border border-[--intel-border]`
- Action buttons: "PLAY TRAILER" (primary blue), "VIEW ON IMDB" (outlined), "ADD TO WATCHLIST" (outlined)
- Cast section: keep circular photos, add monospace labels
- Crew section: monospace labels in `<dl>`
- Reviews section: update colors
- Related articles: update card styling
- Stagger animations: change from translateY to translateX where appropriate

**Step 3: Run lint and build**

Run: `npm run lint && npm run build`
Expected: PASS

**Step 4: Commit**

```bash
git add resources/js/pages/Movies/Show.vue
git commit -m "style: redesign Movies/Show with intelligence dossier layout"
```

---

### Task 19: Redesign Articles/Index page

**Files:**
- Modify: `resources/js/pages/Articles/Index.vue`

**Step 1: Read current Articles/Index.vue**

Read the file. Note: this page does NOT use PublicSection/PublicContainer/SectionHeader — it needs to be brought into the shared system.

**Step 2: Rewrite Articles/Index with shared primitives**

Major structural update:
- Wrap content in `PublicSection` + `PublicContainer`
- Add `SectionHeader` with kicker "INTELLIGENCE" and title "War Film Articles"
- Replace custom search input with shared `Input` component
- Restyle article cards: `border border-[--intel-border] rounded-md bg-[--intel-bg-surface]`
- Title: `font-[family-name:var(--font-mono-display)]`
- Metadata (date, source, author): small monospace
- Tags: monospace badges
- If article has associated movie: show small poster thumbnail + movie title
- Add proper empty state: illustration + "NO INTELLIGENCE REPORTS FOUND" heading
- Pagination: consistent with Movies/Index treatment

**Step 3: Run lint and build**

Run: `npm run lint && npm run build`
Expected: PASS

**Step 4: Commit**

```bash
git add resources/js/pages/Articles/Index.vue
git commit -m "style: redesign Articles/Index with shared primitives and intelligence styling"
```

---

### Task 20: Redesign Articles/Show page

**Files:**
- Modify: `resources/js/pages/Articles/Show.vue`

**Step 1: Read current Articles/Show.vue**

Read the file. Like Articles/Index, this page doesn't use shared primitives.

**Step 2: Rewrite Articles/Show with shared primitives**

- Wrap in `PublicSection` + `PublicContainer` (narrow, for readability)
- Back link: `< BACK TO ARTICLES` in monospace
- Header: title in `font-[family-name:var(--font-mono-display)]`
- Metadata row: date, author, source, views in small monospace
- Tags as badges below header
- Associated movie: small card with poster + title + "VIEW DOSSIER" link, `border border-[--intel-border]`
- Article body: `prose prose-invert` but override `--tw-prose-body` to use IBM Plex Sans colors
- Source attribution at bottom: bordered panel, monospace label

**Step 3: Run lint and build**

Run: `npm run lint && npm run build`
Expected: PASS

**Step 4: Commit**

```bash
git add resources/js/pages/Articles/Show.vue
git commit -m "style: redesign Articles/Show with shared primitives and intelligence styling"
```

---

### Task 21: Redesign Watchlist/Index page

**Files:**
- Modify: `resources/js/pages/Watchlist/Index.vue`

**Step 1: Read current Watchlist/Index.vue**

Read the file. This page already uses shared primitives correctly.

**Step 2: Update Watchlist/Index styling**

- Replace zinc/red colors with intelligence palette
- Replace DotPattern in empty state with CoordinateGrid
- Empty state heading: "NO TARGETS ACQUIRED" in monospace
- Empty state description: "Your watchlist is empty. Browse the database to add films."
- Empty state CTA: "BROWSE DATABASE" in blue button

**Step 3: Run lint and build**

Run: `npm run lint && npm run build`
Expected: PASS

**Step 4: Commit**

```bash
git add resources/js/pages/Watchlist/Index.vue
git commit -m "style: redesign Watchlist/Index with intelligence styling"
```

---

## Phase 5: Cleanup and Review Components

### Task 22: Update PublicLayout and remaining shared components

**Files:**
- Modify: `resources/js/layouts/PublicLayout.vue`
- Modify: `resources/js/components/public/PublicSection.vue`
- Modify: `resources/js/components/public/PublicContainer.vue`
- Modify: `resources/js/components/public/MovieGridSkeleton.vue`

**Step 1: Update PublicLayout**

- Replace `bg-zinc-950` with `bg-[--intel-bg-base]`
- Replace `text-zinc-300` (if any) with `text-[--intel-text-body]`

**Step 2: Update PublicSection**

- Replace any zinc backgrounds with intelligence palette

**Step 3: Update MovieGridSkeleton**

- Replace skeleton pulse colors with intelligence palette (`bg-[--intel-bg-surface]` for skeleton blocks)

**Step 4: Run full test suite**

Run: `npm run lint && npm run build && npm run test`
Expected: ALL PASS

**Step 5: Commit**

```bash
git add resources/js/layouts/PublicLayout.vue resources/js/components/public/PublicSection.vue resources/js/components/public/PublicContainer.vue resources/js/components/public/MovieGridSkeleton.vue
git commit -m "style: update PublicLayout and remaining shared components for intelligence palette"
```

---

### Task 23: Update review/shortcode component styles

**Files:**
- Modify: `resources/css/app.css` (`.review-content`, `.pull-quote`, `.film-ref` classes)

**Step 1: Update component-level CSS**

- `.pull-quote`: change `border-left: 3px solid ... red-500` to `border-left: 3px solid var(--intel-accent)` (blue)
- `.pull-quote` background: `var(--intel-bg-surface)` instead of `zinc-900/50`
- `.pull-quote` text color: `var(--intel-text-primary)` instead of `zinc-200`
- `.film-ref`: change `red-500` to `blue-500`, hover `red-400` to `blue-400`
- `.film-ref--missing`: change `zinc-500` to `var(--intel-text-muted)`
- `.spoiler-block`: update `zinc-700` to `var(--intel-bg-elevated)`, revealed `zinc-300` to `var(--intel-text-body)`

**Step 2: Run lint and build**

Run: `npm run lint && npm run build`
Expected: PASS

**Step 3: Commit**

```bash
git add resources/css/app.css
git commit -m "style: update review shortcode styles for intelligence palette"
```

---

### Task 24: Run full test suite and visual verification

**Step 1: Run all backend tests**

Run: `ddev artisan test --compact`
Expected: ALL PASS

**Step 2: Run all frontend tests**

Run: `npm run test`
Expected: ALL PASS

**Step 3: Run lint and type-check**

Run: `npm run check`
Expected: ALL PASS

**Step 4: Build for production**

Run: `npm run build`
Expected: SUCCESS

**Step 5: Visual verification**

Open the site and verify each page:
- Homepage (`/`)
- Browse movies (`/movies`)
- Movie detail (`/movies/{any-slug}`)
- Articles (`/articles`)
- Article detail (`/articles/{any-slug}`)
- Watchlist (`/watchlist` — requires login)

Check: fonts loaded, colors correct, grid textures visible, animations work, no visual regressions.

**Step 6: Final commit if any fixes needed**

```bash
git add -A
git commit -m "fix: final visual adjustments for intelligence redesign"
```
