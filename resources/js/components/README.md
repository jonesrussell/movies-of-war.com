# Component Usage Patterns

This document outlines the standard patterns and conventions for using components in this codebase.

## Icons

**Pattern**: Direct imports from `lucide-vue-next`

```vue
<script setup lang="ts">
import { Film, Bookmark, Search } from 'lucide-vue-next';
</script>

<template>
  <Film class="h-4 w-4" />
</template>
```

**Rationale**: Direct imports provide better tree-shaking and type safety compared to wrapper components.

**Do NOT use**: The `Icon.vue` wrapper component (removed - unused)

## Layouts

### App Layouts

- **`AppSidebarLayout`**: Primary layout for authenticated dashboard pages
  - Used by: Dashboard, Admin pages, Settings pages
  - Features: Collapsible sidebar, breadcrumbs, user menu

- **`AppLayout`**: Wrapper that delegates to `AppSidebarLayout`
  - Use this wrapper for consistency

### Auth Layouts

- **`AuthLayout`**: Wrapper that delegates to `AuthSimpleLayout`
  - Used by: All authentication pages (Login, Register, etc.)
  - Features: Centered card layout with logo

### Public Layouts

- **`PublicLayout`**: Layout for public-facing pages
  - Used by: Welcome, Movies index/show, Watchlist
  - Features: Public header with navigation

## Heading Components

**Pattern**: Use the unified `Heading` component with size variants

```vue
<script setup lang="ts">
import Heading from '@/components/Heading.vue';
</script>

<template>
  <!-- Small heading (default, h3, text-base) -->
  <Heading title="Section Title" description="Optional description" />
  
  <!-- Large heading (h2, text-xl) -->
  <Heading size="lg" title="Page Title" description="Page description" />
</template>
```

**Sizes**:
- `sm` / `md` (default): h3, text-base, for section headings
- `lg`: h2, text-xl, for page-level headings

## Type Safety

### User Type

Always use the proper `User` type from `@/types/models`:

```typescript
import type { User } from '@/types/models';

const auth = page.props.auth as { user?: User };
```

**Do NOT use**: `as { user: any }` - this defeats TypeScript's purpose

## Component Organization

- **`/components`**: Reusable application components
- **`/components/ui`**: shadcn-vue primitives (do not modify directly)
- **`/components/public`**: Public-facing navigation components
- **`/pages`**: Inertia page components (route-specific)

## Best Practices

1. **Import icons directly** from `lucide-vue-next` - no wrappers
2. **Use Wayfinder** for all route generation - no hardcoded URLs
3. **Type all props** with TypeScript interfaces
4. **Use shadcn components** for UI primitives (Button, Card, etc.)
5. **Follow existing patterns** - check similar components before creating new ones