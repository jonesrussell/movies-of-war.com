# Curator Reviews

This directory contains markdown files that serve as the authoritative editorial reviews for movies-of-war.com. These bypass the database entirely — they are read from disk, rendered, and cached at request time.

## File Format

Each file is named `{movie-slug}.md` and must contain YAML frontmatter:

```markdown
---
title: "Movie Title"
year: 1981
rating: 3          # 0-4 scale, half-stars allowed (e.g. 3.5)
director: "Director Name"
starring: ["Actor One", "Actor Two"]
runtime: 110
slug: optional-override   # defaults to filename without .md
has_spoilers: false        # defaults to false
---

Review body in standard markdown...
```

**Required frontmatter:** `title`
**Optional frontmatter:** `year`, `rating`, `director`, `starring`, `runtime`, `slug`, `has_spoilers`

## Shortcodes

Three custom shortcodes are available in review body text:

| Shortcode | Purpose |
|-----------|---------|
| `[film:movie-slug]` | Links to another movie on the site by slug. Resolves title from DB. |
| `[spoiler]text[/spoiler]` | Blurred text, click to reveal. |
| `[quote]text[/quote]` | Styled pull quote, visually distinct from `>` blockquotes. |

## Naming Convention

Filename must match the movie's slug in the database (e.g. `gallipoli-1981.md` for a movie with slug `gallipoli-1981`). Use the frontmatter `slug` field only if the filename can't match.

## Processing Pipeline

Files are processed by `CuratorReviewService`:
1. Read file + parse YAML frontmatter (`league/commonmark` FrontMatter extension)
2. Render markdown to HTML (`MarkdownRenderer`)
3. Expand shortcodes (`ShortcodeProcessor`) — runs after markdown rendering
4. Cache result (busted by file modification time)

## Key Files

- `app/Services/CuratorReviewService.php` — orchestrates the pipeline
- `app/Services/ShortcodeProcessor.php` — expands shortcodes in rendered HTML
- `app/Data/CuratorReview.php` — DTO passed as Inertia prop
- `resources/js/components/reviews/CuratorReview.vue` — frontend display component

## When Writing Reviews

- Use the `/film-review` skill for drafting reviews in Russell's voice
- Star rating is 0-4 scale (Ebert style), not 0-5
- Standard markdown features work (bold, italic, links, headings, lists, blockquotes)
- Raw HTML is stripped for security — use shortcodes instead
- The `[quote]` shortcode is for highlighted dialogue/pull quotes; use standard `>` for regular blockquotes
