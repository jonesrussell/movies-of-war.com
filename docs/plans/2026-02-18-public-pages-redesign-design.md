# Public Pages Redesign: "Refined Intelligence" Design

## Aesthetic Direction

**Concept:** Refined intelligence — the sophistication of a modern intelligence agency's internal tools. Clean, precise, data-forward. Military precision without theatrics.

**Palette:** Slate Command — cool navy-black surfaces, steel blue accents, crisp typography.

**Approach:** Full system rebuild — new design tokens, new typography, new textures, then cascade through all 6 public pages.

---

## Design System Foundation

### Typography

| Role | Font | Usage |
|------|------|-------|
| Display/Headers | JetBrains Mono | Page titles, section headers, kickers, data labels |
| Body/Reading | IBM Plex Sans | Paragraphs, descriptions, navigation |
| Data/Metadata | JetBrains Mono (small) | Years, runtime, counts, status badges, coordinates |

### Color Tokens

```
--bg-base:       #0c1222    navy black (page background)
--bg-surface:    #131c31    dark slate (cards, panels)
--bg-elevated:   #1a2540    raised elements, hovers
--border:        #1e3050    subtle borders
--border-bright: #2a4060    emphasized borders
--accent:        #3b82f6    command blue (links, active states)
--accent-dim:    #2563eb    hover states
--alert:         #ef4444    destructive actions, warnings
--text-primary:  #e2e8f0    headings, important text
--text-body:     #94a3b8    body text, descriptions
--text-muted:    #64748b    captions, secondary info
--text-faint:    #475569    disabled, decorative
```

### Texture & Atmosphere

- **Coordinate grid:** Replace dot-pattern with thin coordinate grid lines (~40px intervals, opacity 0.03-0.05). Evokes tactical maps.
- **Scan line accent:** Single horizontal rule with faint blue glow as section divider.
- **Topographic hint:** Subtle topo-line SVG on hero sections at very low opacity.

### Motion

- Keep existing cinematic easing curves (`--ease-cinematic`, `--ease-smooth-out`)
- New "data-in" animation: translate-x (slide from left) for hero content
- Pulse animation on "live" status indicators (e.g., NOW SHOWING badges)
- Keep reduced-motion support

### Borders & Radii

- Shift from `rounded-xl/2xl` to `rounded-md` (4px) — sharp, precise edges
- 1px borders on all cards/panels (currently many are borderless)

---

## Shared Components

### PublicHeader

- Dark navy bar with 1px `--border` bottom border
- Logo left, nav links in IBM Plex Sans with letter-spacing
- Active link: underline in `--accent` (command blue)
- Right: "WATCHLIST" with count badge (monospace), auth links
- On scroll: `backdrop-blur` with elevated background, no logo size change
- Mobile: slide-in panel with navy surface, stacked links with horizontal dividers

### PublicFooter (major upgrade)

- Three-column grid:
  - Site description + nav links
  - Quick links (browse, watchlist, articles)
  - "Intelligence" meta (total films, last updated, TMDB attribution)
- Bottom bar: copyright in monospace, operational indicator (`MOW-2026.02 // OPERATIONAL`)
- Coordinate grid texture at low opacity

### SectionHeader

- Kicker: monospace, uppercase, letter-spaced, `--accent` with horizontal line prefix (`--- BROWSE`)
- Title: JetBrains Mono, large, `--text-primary`
- Description: IBM Plex Sans, `--text-body`

### MovieCard

- `rounded-md`, 1px `--border`
- Hover: border -> `--accent`, subtle blue glow shadow
- Tags: small monospace badges below poster
- Status: corner dot indicator (green = published, amber = upcoming)
- Rating: monospace digits with small bar indicator (no stars)

### MovieGrid

- Keep container-query responsive (3 -> 4 -> 6 cols)
- Thin `--border` grid lines between cards

---

## Page Designs

### Welcome (Homepage)

**Hero (Pick of the Week):**
- Blurred poster background (retained)
- Coordinate grid overlay + gradient
- `PRIORITY BRIEFING // PICK OF THE WEEK` monospace kicker in blue
- Title in large JetBrains Mono
- CTA buttons: "VIEW DOSSIER" (primary blue), "ADD TO WATCHLIST" (outlined)
- Staggered left-slide entrance animations

**Featured Upcoming:**
- Horizontal card, `INCOMING // FEATURED RELEASE` kicker
- Release date as countdown: `T-45 DAYS` monospace
- 1px bordered card on `--bg-surface`

**Latest Releases / Upcoming:** Standard SectionHeader + MovieGrid

**Remove:** X Feed Widget, generic CTA banner. Replace with "Intel Summary" data row: total films, genres, decades.

### Movies/Index (Browse)

- `--- BROWSE` kicker + "War Films Database" title + `247 ENTRIES INDEXED` count
- Quick filter pills: monospace, 1px borders, blue active state
- Controls: search input (monospace placeholder), shadcn Select for sort, view toggle, filter button
- Filter panel: shadcn Select components in 4-column grid (replace raw `<select>`)
- Data header: `SHOWING 1-24 OF 247 // SORTED BY: RELEASE YEAR DESC` monospace
- Grid: new MovieCard style with grid-line separators
- List: data-dense horizontal rows with inline monospace metadata
- Pagination: monospace page numbers, blue active

### Movies/Show (Detail)

- Hero: blurred poster retained, coordinate grid replaces dots
- Back: `< BACK TO INDEX` monospace
- Poster: `rounded-md`, 1px border, blue hover glow
- Title: JetBrains Mono fluid
- MovieFacts: data grid with label/value pairs, monospace values, thin dividers. Fix duplicate icon (Swords for conflict, Clapperboard for production)
- Tags: monospace badges, horizontal row
- Cast: circular photos + monospace name labels
- Crew: clean dl with monospace labels
- Actions: "PLAY TRAILER" (blue), "VIEW ON IMDB" (outlined), "ADD TO WATCHLIST" (tertiary)
- Animations: slide from left

### Articles/Index

- Bring into shared system: PublicSection, PublicContainer, SectionHeader
- `--- INTELLIGENCE` kicker + "War Film Articles"
- Search: shared Input component
- Article cards: 1px bordered, title in display font, metadata in monospace
- Movie link: small poster thumbnail + title
- Proper empty state with illustration
- Consistent pagination

### Articles/Show

- Bring into shared system: PublicSection, PublicContainer (max-w-3xl)
- Back: `< BACK TO ARTICLES` monospace
- Header: JetBrains Mono title, monospace metadata row
- Tags as badges
- Movie association: small card with poster + "VIEW DOSSIER" link
- Body: prose with IBM Plex Sans, refined line-height
- Source: bordered panel at bottom

### Watchlist/Index

- Restyle with new tokens and card treatment
- Empty state: intelligence tone ("NO TARGETS ACQUIRED" heading)

---

## Scope Summary

**Files to create/modify:** ~20+ Vue components, app.css theme, potentially new primitive components (CoordinateGrid, ScanLine)

**Pages affected:** Welcome, Movies/Index, Movies/Show, Articles/Index, Articles/Show, Watchlist/Index

**New external fonts:** JetBrains Mono (Google Fonts), IBM Plex Sans (Google Fonts)

**Components to redesign:** PublicHeader, PublicFooter, SectionHeader, MovieCard, MovieGrid, MovieHero, FeaturedMovie, MovieFacts, MoviesFiltersPanel, FilterChip, MovieListItem

**Components to bring into consistency:** Articles/Index, Articles/Show (adopt shared primitives)

**Components to remove/replace:** DotPattern primitive -> CoordinateGrid primitive
