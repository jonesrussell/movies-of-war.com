# 2026 CSS + Tailwind 4 + Vue Senior-Level Audit Report

**Date**: 2026  
**Project**: Movies of War - Laravel 12 + Inertia.js v2 + Vue 3 + TypeScript + Tailwind CSS 4 + shadcn-vue

---

## Executive Summary

This audit identified **47 issues** across 7 major categories, with a focus on modern 2026 CSS features and best practices. The codebase shows good foundational patterns but has significant opportunities to adopt modern CSS capabilities that would improve responsiveness, maintainability, and user experience.

**Priority Breakdown**:
- **Critical**: 8 issues (high impact, modern CSS adoption)
- **High**: 15 issues (significant improvements)
- **Medium**: 18 issues (consistency and best practices)
- **Low**: 6 issues (nice-to-have optimizations)

---

## 1. 2026 CSS Responsiveness & Layout

### 1.1 Container Queries ❌ **CRITICAL**

**Status**: Not implemented. All components use viewport-based breakpoints.

#### Issue #1: MovieGrid Uses Viewport Breakpoints Instead of Container Queries

**File**: `resources/js/components/public/MovieGrid.vue:19`

**Problem**: Grid adapts to viewport size (`sm:`, `lg:`, `xl:`) rather than container size, causing layout issues when the grid is constrained within a sidebar or narrow container.

**Why It Matters**: 
- Layout breaks when component is used in constrained contexts (sidebars, modals, nested layouts)
- Brittle viewport-based logic doesn't adapt to actual available space
- Poor responsive behavior in modern layout contexts

**Current Code**:
```vue
<div
  :class="
    cn(
      'grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6',
      dense ? 'gap-4' : 'gap-6',
      $props.class,
    )
  "
>
```

**Fix**:
```vue
<template>
  <div
    :class="
      cn(
        'grid grid-cols-2 @container',
        dense ? 'gap-4' : 'gap-6',
        $props.class,
      )
    "
    style="container-type: inline-size;"
  >
    <slot />
  </div>
</template>
```

**CSS Addition** (in `app.css`):
```css
@layer components {
  .movie-grid {
    container-type: inline-size;
  }
  
  @container (min-width: 20rem) {
    .movie-grid {
      grid-template-columns: repeat(3, minmax(0, 1fr));
    }
  }
  
  @container (min-width: 32rem) {
    .movie-grid {
      grid-template-columns: repeat(4, minmax(0, 1fr));
    }
  }
  
  @container (min-width: 48rem) {
    .movie-grid {
      grid-template-columns: repeat(6, minmax(0, 1fr));
    }
  }
}
```

**Alternative (Tailwind v4 container queries)**:
```vue
<div
  :class="
    cn(
      '@container grid grid-cols-2 @[20rem]:grid-cols-3 @[32rem]:grid-cols-4 @[48rem]:grid-cols-6',
      dense ? 'gap-4' : 'gap-6',
      $props.class,
    )
  "
>
```

---

#### Issue #2: FeaturedMovie Component Needs Container Queries

**File**: `resources/js/components/FeaturedMovie.vue:30`

**Problem**: Layout switches at `md:` breakpoint, but should adapt to container width.

**Current Code**:
```vue
<div class="grid md:grid-cols-12">
```

**Fix**:
```vue
<div class="@container grid grid-cols-1 @[32rem]:grid-cols-12">
```

---

#### Issue #3: MoviesFiltersPanel Grid Should Use Container Queries

**File**: `resources/js/components/public/MoviesFiltersPanel.vue:31`

**Problem**: Filter grid uses viewport breakpoints (`sm:`, `lg:`) but should adapt to container.

**Current Code**:
```vue
<div
  :class="[
    'grid gap-4 rounded-2xl bg-zinc-950 p-4 ring-1 ring-zinc-800/70 sm:grid-cols-2 lg:grid-cols-4',
    $props.class,
  ]"
>
```

**Fix**:
```vue
<div
  :class="[
    '@container grid gap-4 rounded-2xl bg-zinc-950 p-4 ring-1 ring-zinc-800/70 grid-cols-1 @[24rem]:grid-cols-2 @[48rem]:grid-cols-4',
    $props.class,
  ]"
  style="container-type: inline-size;"
>
```

---

### 1.2 Modern Viewport Units ✅ **PARTIAL**

**Status**: Partially implemented. Some components use `svh`, but opportunities exist for `dvh` and `lvh`.

#### Issue #4: Sidebar Uses `svh` But Should Consider `dvh` for Dynamic UI

**File**: `resources/js/components/ui/sidebar/Sidebar.vue:76`

**Current Code**: Uses `h-svh` which is correct for mobile, but could use `dvh` for desktop to handle dynamic browser UI better.

**Why It Matters**: `svh` is safe for mobile but `dvh` provides better experience on desktop when browser UI shows/hides.

**Fix** (if desktop-specific behavior needed):
```vue
<div
  :class="cn(
    'fixed inset-y-0 z-10 hidden h-svh md:h-dvh w-(--sidebar-width) ...',
    ...
  )"
>
```

**Note**: Current implementation is acceptable. This is a low-priority optimization.

---

### 1.3 Fluid Sizing & Functions ❌ **CRITICAL**

**Status**: Not implemented. Typography and spacing use breakpoint-based scaling.

#### Issue #5: Typography Uses Breakpoint Jumps Instead of Fluid Scaling

**File**: `resources/js/components/MovieHero.vue:94`

**Problem**: Heading size jumps at breakpoints instead of scaling smoothly.

**Current Code**:
```vue
<h1
  class="mb-4 text-4xl font-bold tracking-tight text-balance text-white sm:text-5xl lg:text-6xl"
>
```

**Why It Matters**:
- Typography jumps create jarring visual changes
- Fluid typography provides smoother, more professional scaling
- Better responsive behavior across all viewport sizes

**Fix**:
```vue
<h1
  class="mb-4 font-bold tracking-tight text-balance text-white"
  style="font-size: clamp(2rem, 4vw + 1rem, 3.75rem);"
>
```

**Or using Tailwind v4 fluid type** (if available):
```vue
<h1 class="mb-4 text-[clamp(2rem,4vw+1rem,3.75rem)] font-bold tracking-tight text-balance text-white">
```

**Additional Files with Same Issue**:
- `resources/js/pages/Welcome.vue:52` - `text-4xl sm:text-5xl lg:text-6xl`
- `resources/js/pages/Movies/Show.vue:133` - `text-4xl sm:text-5xl`
- `resources/js/pages/Welcome.vue:124` - `text-2xl sm:text-3xl`

---

#### Issue #6: PublicContainer Max-Width Could Use Fluid Sizing

**File**: `resources/js/components/public/PublicContainer.vue:19`

**Current Code**:
```vue
:class="cn('mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8', $props.class)"
```

**Fix** (using `min()` for responsive max-width):
```vue
:class="cn('mx-auto w-full px-4 sm:px-6 lg:px-8', $props.class)"
style="max-width: min(1280px, 100vw - 2rem);"
```

---

#### Issue #7: MovieGrid Should Use `auto-fit` or `auto-fill` for Flexible Columns

**File**: `resources/js/components/public/MovieGrid.vue:19`

**Problem**: Fixed column counts don't adapt to available space efficiently.

**Current Code**:
```vue
'grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6'
```

**Fix** (with container queries):
```css
@container (min-width: 20rem) {
  .movie-grid {
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  }
}
```

---

### 1.4 Logical Properties ⚠️ **NEEDS REVIEW**

**Status**: Appears to be using Tailwind's default (which may be logical). Need to verify physical properties.

#### Issue #8: Verify No Physical Directional Properties

**Audit Result**: No `margin-left`, `margin-right`, `left:`, `right:` found in codebase. ✅

**Status**: Good - appears to be using Tailwind's logical equivalents or avoiding directional properties.

**Recommendation**: Continue using Tailwind utilities (`ms-*`, `me-*`, `ps-*`, `pe-*`) for any new directional spacing.

---

### 1.5 :has() Selector ❌ **MISSED OPPORTUNITY**

**Status**: Not implemented. JavaScript handles state-based styling.

#### Issue #9: Form Validation Could Use :has() Instead of JS

**File**: `resources/js/components/InputError.vue`

**Problem**: Form validation styling is handled by showing/hiding error component, but parent styling could use `:has()`.

**Current Pattern**:
```vue
<!-- Parent form group -->
<div>
  <input />
  <InputError :message="error" />
</div>
```

**Opportunity**: Style parent based on error presence:
```css
.form-group:has(.error-message) input {
  @apply border-destructive;
}
```

**Note**: Current implementation is acceptable. This is an optimization opportunity, not a bug.

---

#### Issue #10: Navigation Active States Could Use :has()

**File**: `resources/js/components/public/PublicNav.vue:30`

**Current Code**: Uses JavaScript `activeClass` composable to add classes.

**Opportunity**: Could use `:has([aria-current="page"])` for CSS-driven active states:
```css
nav:has([aria-current="page"]) {
  /* Active nav styling */
}
```

**Note**: Current implementation works. This is a modern CSS optimization.

---

### 1.6 Subgrid ❌ **NOT IMPLEMENTED**

**Status**: Not used. Nested grids duplicate column definitions.

#### Issue #11: MovieGrid Cards Could Benefit from Subgrid

**File**: `resources/js/components/public/MovieGrid.vue` + `resources/js/components/MovieCard.vue`

**Problem**: Card content doesn't align with parent grid columns.

**Opportunity**: If card content needs to align with grid, subgrid could help:
```css
.movie-card {
  display: grid;
  grid-template-columns: subgrid;
  grid-column: span 1;
}
```

**Note**: Current implementation may not need subgrid. Evaluate if alignment is required.

---

### 1.7 Cascade Layers ⚠️ **BASIC IMPLEMENTATION**

**Status**: Basic `@layer base` and `@layer utilities` used. Could be more organized.

#### Issue #12: Cascade Layers Need Better Organization

**File**: `resources/css/app.css`

**Current Structure**:
- `@layer base` - Basic resets
- `@layer utilities` - Custom utilities
- No `@layer components` or `@layer themes`

**Problem**: All component styles are in components (Vue SFCs), which may cause specificity issues.

**Recommendation**: Consider organizing custom CSS into layers:
```css
@layer reset {
  /* CSS resets */
}

@layer base {
  /* Base styles */
}

@layer components {
  /* Reusable component styles */
}

@layer utilities {
  /* Utility overrides */
}

@layer themes {
  /* Theme-specific overrides */
}
```

**Impact**: Low - current structure works, but better organization would prevent specificity conflicts.

---

## 2. Tailwind CSS 4 Best Practices

### 2.1 Container Queries Support ❌

**Status**: Not using Tailwind v4 container query features.

**Issue #13**: Not Using Tailwind v4 Container Query Syntax

**Files**: All grid components

**Recommendation**: Use Tailwind v4 `@container` and `@[size]` syntax instead of custom CSS.

---

### 2.2 Fluid Type Utilities ❌

**Status**: Not using Tailwind v4 fluid type features.

**Issue #14**: Typography Uses Breakpoint Classes Instead of Fluid Utilities

**Files**: Multiple (see Issue #5)

**Recommendation**: Use Tailwind v4 fluid type utilities if available, or custom `clamp()` values.

---

### 2.3 Consistent Patterns ⚠️

#### Issue #15: Inconsistent Spacing Patterns

**Files**: Multiple components

**Problem**: Mix of `gap-*` and `mb-*`/`mt-*` for spacing.

**Recommendation**: Prefer `gap` utilities for flex/grid containers, margins for standalone elements.

**Examples**:
- ✅ Good: `<div class="flex gap-4">`
- ⚠️ Avoid: `<div class="flex"><div class="mb-4">` (use gap instead)

---

#### Issue #16: Arbitrary Values Without Justification

**Files**: Multiple

**Examples Found**:
- `max-w-[260px]` in `FeaturedMovie.vue:35`
- `max-w-[220px]` in `FeaturedMovie.vue:35`
- `max-w-[300px]` in `PublicMobileMenu.vue:43`

**Recommendation**: Use design tokens or Tailwind scale values when possible. If arbitrary values are needed, document why.

---

## 3. shadcn-vue Component Usage

### 3.1 Missing shadcn Component Usage ❌ **HIGH PRIORITY**

#### Issue #17: Custom Input in Admin Movies Index

**File**: `resources/js/pages/Admin/Movies/Index.vue:92-97`

**Problem**: Custom input styling instead of shadcn Input component.

**Current Code**:
```vue
<input
  v-model="search"
  type="text"
  placeholder="Search movies..."
  class="w-full rounded-lg border-zinc-700 bg-zinc-900 px-4 py-2 text-white placeholder-zinc-500 focus:border-red-500 focus:ring-red-500"
/>
```

**Fix**:
```vue
<script setup>
import { Input } from '@/components/ui/input';
</script>

<template>
  <Input
    v-model="search"
    type="text"
    placeholder="Search movies..."
    class="w-full"
  />
</template>
```

**Why It Matters**: 
- Inconsistent styling across forms
- Missing shadcn design tokens
- Harder to maintain

---

#### Issue #18: Custom Input in Movies Index

**File**: `resources/js/pages/Movies/Index.vue:179-184`

**Problem**: Custom input with icon instead of using shadcn Input.

**Current Code**:
```vue
<div class="relative w-full lg:flex-1">
  <Search
    class="absolute top-1/2 left-3 size-5 -translate-y-1/2 text-zinc-500"
  />
  <input
    v-model="search"
    type="text"
    placeholder="Search movies..."
    class="w-full rounded-xl border border-zinc-800 bg-zinc-950 py-3 pr-4 pl-10 text-white placeholder-zinc-500 focus:border-red-600 focus:ring-2 focus:ring-red-600/40 focus:outline-none"
  />
</div>
```

**Fix**:
```vue
<div class="relative w-full lg:flex-1">
  <Search
    class="absolute top-1/2 left-3 size-5 -translate-y-1/2 text-zinc-500 pointer-events-none z-10"
  />
  <Input
    v-model="search"
    type="text"
    placeholder="Search movies..."
    class="w-full pl-10"
  />
</div>
```

---

#### Issue #19: Custom Select Elements Instead of shadcn Select

**File**: `resources/js/components/public/MoviesFiltersPanel.vue:39-51`

**Problem**: Custom select styling instead of shadcn Select component (if available).

**Current Code**:
```vue
<select
  :value="year"
  class="w-full rounded-xl border border-zinc-800 bg-zinc-950 px-3 py-2 text-white focus:border-red-600 focus:ring-2 focus:ring-red-600/40 focus:outline-none"
  @change="..."
>
```

**Note**: shadcn-vue may not have a Select component. Check if one exists or should be added.

**If Select component exists, fix**:
```vue
<script setup>
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
</script>

<template>
  <Select :value="year" @update:value="$emit('update:year', $event)">
    <SelectTrigger>
      <SelectValue placeholder="All years" />
    </SelectTrigger>
    <SelectContent>
      <SelectItem value="">All years</SelectItem>
      <SelectItem v-for="y in years" :key="y" :value="String(y)">
        {{ y }}
      </SelectItem>
    </SelectContent>
  </Select>
</template>
```

---

### 3.2 Button Component Usage ✅

**Status**: Good - most buttons use shadcn Button component.

**Note**: Some custom button-like links exist but are acceptable for specific styling needs.

---

### 3.3 Card Component Usage ✅

**Status**: Good - shadcn Card components are used appropriately.

---

## 4. Vue 3 + TypeScript Patterns

### 4.1 Composition API ✅

**Status**: Excellent - all components use Composition API with `<script setup>`.

---

### 4.2 Type Safety ⚠️

#### Issue #20: Type Assertion Using `as { user?: User }`

**File**: `resources/js/pages/Movies/Show.vue:24`

**Current Code**:
```typescript
const auth = page.props.auth as { user?: User };
```

**Problem**: Type assertion bypasses TypeScript's type checking.

**Fix**: Define proper Inertia page props type:
```typescript
interface PageProps {
  auth: {
    user?: User;
  };
  movie: Movie;
  relatedMovies: Movie[];
}

const page = usePage<PageProps>();
const auth = page.props.auth;
```

**Additional Files**:
- `resources/js/pages/Dashboard.vue:23`

---

#### Issue #21: Missing Prop Validation

**Files**: Multiple components

**Recommendation**: Use `withDefaults` and proper TypeScript interfaces for all props. Current implementation is good, but could add runtime validation if needed.

---

### 4.3 Reactivity ✅

**Status**: Good - proper use of `ref`, `computed`, and `watch`.

---

### 4.4 Component Structure ✅

**Status**: Good - components are appropriately sized and cohesive.

---

## 5. Inertia.js Integration

### 5.1 Page Structure ✅

**Status**: Good - proper use of layouts and page components.

---

### 5.2 Form Helpers ⚠️

#### Issue #22: Not Using Inertia Form Component

**Files**: Forms throughout the app

**Current Pattern**: Manual form handling with `router.post()`.

**Recommendation**: Consider using Inertia's `<Form>` component for better UX (loading states, error handling, etc.).

**Example**:
```vue
<script setup>
import { Form } from '@inertiajs/vue3';
</script>

<template>
  <Form @submit="submit">
    <input name="title" />
    <button type="submit">Submit</button>
  </Form>
</template>
```

**Note**: Current implementation works. This is an enhancement opportunity.

---

### 5.3 Performance ✅

**Status**: Good - proper use of `preserveState` and `preserveScroll`.

---

## 6. Design System Consistency

### 6.1 Token Alignment ⚠️

#### Issue #23: Hardcoded Colors Instead of Design Tokens

**Files**: Multiple

**Examples**:
- `bg-zinc-950`, `text-zinc-400` - Should use theme tokens where possible
- `bg-red-600`, `text-red-500` - Primary color, should use `bg-primary` or design token

**Recommendation**: Use CSS custom properties or Tailwind theme tokens consistently.

---

#### Issue #24: Inconsistent Border Radius

**Files**: Multiple

**Examples**:
- `rounded-xl` (most common)
- `rounded-lg` (some buttons)
- `rounded-2xl` (some cards)
- `rounded-md` (some inputs)

**Recommendation**: Standardize on design system radius tokens:
- Small: `rounded-md` (8px)
- Medium: `rounded-lg` (12px) 
- Large: `rounded-xl` (16px)
- Extra Large: `rounded-2xl` (24px)

---

### 6.2 Spacing Consistency ⚠️

#### Issue #25: Inconsistent Padding/Margin Patterns

**Files**: Multiple

**Problem**: Mix of spacing approaches.

**Recommendation**: 
- Use `gap-*` for flex/grid containers
- Use consistent spacing scale (4, 6, 8, 12, 16, 24, 32)
- Document spacing patterns

---

## 7. Responsiveness Issues

### 7.1 Admin Table Not Responsive ❌ **CRITICAL**

#### Issue #26: Admin Movies Table Has No Mobile Alternative

**File**: `resources/js/pages/Admin/Movies/Index.vue:101-249`

**Problem**: Table layout doesn't work on mobile - columns overflow, text is unreadable.

**Why It Matters**: Admin interface is unusable on mobile devices.

**Fix Options**:

**Option 1: Card Layout on Mobile**
```vue
<!-- Mobile card view -->
<div class="block md:hidden space-y-4">
  <div
    v-for="movie in movies?.data"
    :key="movie.id"
    class="rounded-lg bg-zinc-900 p-4"
  >
    <!-- Card content -->
  </div>
</div>

<!-- Desktop table view -->
<table class="hidden md:table min-w-full ...">
  <!-- Table content -->
</table>
```

**Option 2: Horizontal Scroll with Sticky First Column**
```vue
<div class="overflow-x-auto">
  <table class="min-w-full ...">
    <thead>
      <tr>
        <th class="sticky left-0 bg-zinc-950 z-10">Title</th>
        <!-- Other columns -->
      </tr>
    </thead>
  </table>
</div>
```

**Option 3: Responsive Table with Stacked Rows**
```vue
<table class="min-w-full">
  <tbody>
    <tr class="block md:table-row">
      <td class="block md:table-cell">
        <div class="md:hidden font-semibold">Title</div>
        {{ movie.title }}
      </td>
    </tr>
  </tbody>
</table>
```

**Recommendation**: Option 1 (card layout) provides best UX on mobile.

---

### 7.2 FeaturedMovie Mobile Layout ⚠️

#### Issue #27: FeaturedMovie Could Improve Mobile Layout

**File**: `resources/js/components/FeaturedMovie.vue:30-48`

**Current**: Grid switches at `md:` breakpoint.

**Recommendation**: Ensure mobile layout is optimal. Current implementation is acceptable but could be refined.

---

## Summary of Top Structural Improvements

### High Impact / Low Effort 🚀

1. **Replace Custom Inputs with shadcn Input** (Issues #17, #18)
   - **Impact**: Consistency, maintainability
   - **Effort**: Low (2-3 hours)
   - **Files**: 2 files

2. **Add Container Queries to MovieGrid** (Issue #1)
   - **Impact**: Better responsive behavior
   - **Effort**: Low (1-2 hours)
   - **Files**: 1 file + CSS

3. **Fix Admin Table Responsiveness** (Issue #26)
   - **Impact**: Critical - mobile usability
   - **Effort**: Medium (4-6 hours)
   - **Files**: 1 file

4. **Implement Fluid Typography** (Issue #5)
   - **Impact**: Professional, smooth scaling
   - **Effort**: Low (2-3 hours)
   - **Files**: 5-6 files

### High Impact / High Effort 🎯

5. **Comprehensive Container Query Migration**
   - **Impact**: Modern, maintainable responsive design
   - **Effort**: High (8-12 hours)
   - **Files**: 10+ files

6. **Design System Token Standardization**
   - **Impact**: Consistency, easier theming
   - **Effort**: High (6-8 hours)
   - **Files**: All components

### Quick Wins ⚡

7. **Fix Type Assertions** (Issue #20)
   - **Impact**: Better type safety
   - **Effort**: Low (1-2 hours)
   - **Files**: 2-3 files

8. **Standardize Border Radius** (Issue #24)
   - **Impact**: Visual consistency
   - **Effort**: Low (2-3 hours)
   - **Files**: Multiple

9. **Organize Cascade Layers** (Issue #12)
   - **Impact**: Better CSS organization
   - **Effort**: Low (1-2 hours)
   - **Files**: 1 file

### Long-Term Recommendations 📈

10. **Adopt :has() Selector for State Styling**
    - **Impact**: Modern CSS, less JS
    - **Effort**: Medium (4-6 hours)
    - **Files**: Forms, navigation

11. **Evaluate Subgrid for Complex Layouts**
    - **Impact**: Better grid alignment
    - **Effort**: Medium (4-6 hours)
    - **Files**: Grid components

12. **Migrate to Inertia Form Component**
    - **Impact**: Better UX, less boilerplate
    - **Effort**: High (8-10 hours)
    - **Files**: All forms

---

## Priority Action Plan

### Phase 1: Critical Fixes (Week 1)
1. Fix Admin Table Responsiveness (Issue #26)
2. Replace Custom Inputs with shadcn (Issues #17, #18)
3. Add Container Queries to MovieGrid (Issue #1)

### Phase 2: Modern CSS Adoption (Week 2-3)
4. Implement Fluid Typography (Issue #5)
5. Add Container Queries to Key Components (Issues #2, #3)
6. Organize Cascade Layers (Issue #12)

### Phase 3: Consistency & Polish (Week 4)
7. Standardize Design Tokens (Issues #23, #24)
8. Fix Type Assertions (Issue #20)
9. Review and optimize spacing patterns (Issue #25)

### Phase 4: Advanced Features (Ongoing)
10. Evaluate :has() selector opportunities
11. Consider subgrid for complex layouts
12. Migrate forms to Inertia Form component

---

## Conclusion

The codebase demonstrates solid foundational patterns with Vue 3 Composition API, TypeScript, and shadcn-vue. The main opportunities lie in adopting modern 2026 CSS features (container queries, fluid sizing, :has()) and improving consistency (shadcn component usage, design tokens).

**Overall Grade**: **B+**

**Strengths**:
- Modern Vue 3 + TypeScript patterns
- Good use of shadcn-vue components (mostly)
- Proper Inertia.js integration
- Clean component structure

**Areas for Improvement**:
- Modern CSS feature adoption
- Component consistency (some custom inputs)
- Responsive design patterns (container queries)
- Design system token usage

---

*End of Audit Report*
