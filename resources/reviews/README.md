# Curator Reviews

Editorial reviews for movies-of-war.com, written as markdown files with YAML frontmatter. These are the site's authoritative reviews, separate from the database-backed community review system.

## Processing Pipeline

```mermaid
flowchart TD
    A[Markdown file<br><code>resources/reviews/slug.md</code>] --> B{File exists?}
    B -- No --> C[Return null]
    B -- Yes --> D{Cache valid?<br><small>mtime unchanged</small>}
    D -- Yes --> E[Return cached CuratorReview]
    D -- No --> F[Parse YAML frontmatter<br><small>league/commonmark FrontMatterExtension</small>]
    F --> G{Valid frontmatter?<br><small>title required</small>}
    G -- No --> H[Log warning, return null]
    G -- Yes --> I[Render markdown to HTML<br><small>MarkdownRenderer</small>]
    I --> J[Process shortcodes<br><small>ShortcodeProcessor</small>]
    J --> K[Build CuratorReview DTO]
    K --> L[Cache result + mtime]
    L --> E
```

## Shortcodes

```mermaid
flowchart LR
    subgraph Input [Markdown source]
        Q["[quote]text[/quote]"]
        S["[spoiler]text[/spoiler]"]
        F["[film:slug]"]
    end

    subgraph Output [Rendered HTML]
        QO["&lt;blockquote class='pull-quote'&gt;"]
        SO["&lt;span class='spoiler-block'&gt;"]
        FO["&lt;a class='film-ref'&gt;Movie Title&lt;/a&gt;"]
        FM["&lt;span class='film-ref--missing'&gt;"]
    end

    Q --> QO
    S --> SO
    F -- slug found --> FO
    F -- slug missing --> FM
```

## Architecture

```mermaid
graph TB
    subgraph Filesystem ["Filesystem (source of truth)"]
        MD["resources/reviews/*.md"]
    end

    subgraph Backend [Laravel]
        SVC[CuratorReviewService]
        SCP[ShortcodeProcessor]
        MR[MarkdownRenderer]
        DTO[CuratorReview DTO]
        Cache[(Laravel Cache)]
    end

    subgraph Frontend [Vue / Inertia]
        MC[MovieController::show]
        CR[CuratorReview.vue]
        RC[ReviewContent.vue]
    end

    subgraph Database [Community Reviews]
        DB[(reviews table)]
        RV[Review model]
    end

    MD --> SVC
    SVC --> MR
    SVC --> SCP
    SVC --> DTO
    SVC --> Cache
    DTO --> MC
    MC --> CR
    DB --> RV --> RC

    style Filesystem fill:#1a1a2e,stroke:#e94560,color:#eee
    style Backend fill:#16213e,stroke:#0f3460,color:#eee
    style Frontend fill:#0f3460,stroke:#533483,color:#eee
    style Database fill:#1a1a2e,stroke:#533483,color:#eee
```

## File Format

```yaml
---
title: "Movie Title"          # required
year: 1981                     # release year
rating: 3                      # 0-4 scale, half-stars allowed
director: "Director Name"
starring: ["Actor One", "Actor Two"]
runtime: 110                   # minutes
slug: custom-slug              # optional, defaults to filename
has_spoilers: false            # optional, defaults to false
---
```

## Writing a Review

1. Create `resources/reviews/{movie-slug}.md` (slug must match the movie's slug in the database)
2. Add YAML frontmatter with at least `title`
3. Write the review body in standard markdown
4. Use `[quote]`, `[spoiler]`, and `[film:]` shortcodes as needed
5. The review appears on the movie's detail page on next request (cached after first parse)

See `CLAUDE.md` in this directory for technical details.
