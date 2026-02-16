# Curator Reviews from Filesystem

## Problem

Curator (editorial) reviews should live as markdown files in the repo, version-controlled and editable in any text editor. They bypass the database entirely and render alongside the existing DB-backed community review system.

## File Format

Files live in `resources/reviews/{movie-slug}.md`:

```markdown
---
title: "Gallipoli"
year: 1981
rating: 3
director: "Peter Weir"
starring: ["Mark Lee", "Mel Gibson", "Bill Kerr", "Robert Grubb"]
runtime: 110
slug: gallipoli-1981          # optional, defaults to filename
has_spoilers: false            # optional, defaults to false
---

Review body with markdown...

[quote]"You're always chipper."[/quote]

Shares DNA with [film:saving-private-ryan]...

The final charge is [spoiler]a massacre[/spoiler].
```

Filename-as-slug by default, frontmatter `slug` field overrides.

## Processing Pipeline

```
Read file → Parse frontmatter → Validate → Render markdown → Process shortcodes → Cache
```

1. Read file, parse frontmatter via `league/commonmark` FrontMatter extension (already installed)
2. Validate frontmatter fields in the DTO (rating 0-4, title required)
3. Render markdown to HTML via existing `MarkdownRenderer` — shortcodes pass through as plain text
4. Post-process: regex finds shortcode patterns in rendered HTML, replaces with styled markup
5. Cache result keyed on `curator_review:{slug}`, busted by file modification time

Post-processing after CommonMark keeps `html_input: 'strip'` security intact.

## Shortcodes (v1)

| Shortcode | Output | Notes |
|-----------|--------|-------|
| `[film:slug]` | `<a href="/movies/{slug}" class="film-ref" data-slug="...">Movie Title</a>` | DB lookup for title, cached. Missing slug renders `<span class="film-ref film-ref--missing">[slug]</span>` |
| `[spoiler]text[/spoiler]` | `<span class="spoiler-block">text</span>` | CSS blur + click-to-reveal via Vue mounted hook |
| `[quote]text[/quote]` | `<blockquote class="pull-quote">text</blockquote>` | Visually distinct from standard `>` blockquotes |

ShortcodeProcessor is a pure transformation — DB access injected via constructor, no side effects.

## New Files

| File | Purpose |
|------|---------|
| `app/Services/CuratorReviewService.php` | Reads files, orchestrates pipeline, manages cache |
| `app/Data/CuratorReview.php` | DTO — validated frontmatter fields + rendered HTML |
| `app/Services/ShortcodeProcessor.php` | Post-processes HTML, expands shortcodes |
| `resources/js/components/reviews/CuratorReview.vue` | Displays review with prose styling, spoiler interactivity |

## Integration

`MovieController::show()` passes curator review as a prop:

```php
'curator_review' => $curatorReviewService->forMovie($movie->slug),
```

`Movies/Show.vue` renders `CuratorReview` component above community reviews. Component handles `null` gracefully (no review = no section rendered).

## Cache Strategy

- Key: `curator_review:{slug}`
- Each request checks file `mtime` against cached `mtime` — file change busts cache automatically
- No manual clearing needed
- Optional deploy hook to warm cache in production

## Coexistence with DB Reviews

- Filesystem = curator's editorial review (prominent, one per movie)
- Database = community reviews (user-submitted, below curator review)
- Two separate systems, two separate rendering paths
- Both use the same `MarkdownRenderer` and Tailwind Typography styling
