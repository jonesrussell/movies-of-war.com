<script setup lang="ts">
import type {
    Movie,
    MovieFilters,
    MovieSortOption,
    PaginatedMovies,
    Tag,
} from '@/types';

import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useDebounceFn, useStorage } from '@vueuse/core';
import {
    ArrowDownAZ,
    ArrowUpAZ,
    Calendar,
    CalendarArrowDown,
    CalendarArrowUp,
    ChevronDown,
    Filter,
    Grid3X3,
    List,
    Loader2,
    Search,
    SlidersHorizontal,
    X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import MovieCard from '@/components/MovieCard.vue';
import MovieCardActions from '@/components/MovieCardActions.vue';
import MoviePreviewDialog from '@/components/MoviePreviewDialog.vue';
import FilterChip from '@/components/public/FilterChip.vue';
import MovieGrid from '@/components/public/MovieGrid.vue';
import MovieGridSkeleton from '@/components/public/MovieGridSkeleton.vue';
import MovieListItem from '@/components/public/MovieListItem.vue';
import MoviesFiltersPanel from '@/components/public/MoviesFiltersPanel.vue';
import PublicContainer from '@/components/public/PublicContainer.vue';
import PublicSection from '@/components/public/PublicSection.vue';
import SectionHeader from '@/components/public/SectionHeader.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useInfiniteScroll } from '@/composables/use-infinite-scroll';
import { useViewMode } from '@/composables/use-view-mode';
import PublicLayout from '@/layouts/PublicLayout.vue';

/** Tags may be a plain array or a wrapped object { data: Tag[] } from API resource. */
function normalizeFilterTags(tags: Tag[] | { data?: Tag[] } | undefined): Tag[] {
    if (Array.isArray(tags)) {
        return tags;
    }
    return tags?.data ?? [];
}

interface Props {
    filters: {
        conflicts: string[];
        countries: string[];
        tags: Tag[] | { data?: Tag[] };
        years: number[];
    };
    movies: PaginatedMovies & { watchlisted_ids?: number[] };
    queryParams: MovieFilters;
}

const props = defineProps<Props>();
const filterTags = computed(() => normalizeFilterTags(props.filters.tags));
const page = usePage();
const appUrl = computed(() => (page.props.appUrl as string) ?? '');
const canonicalUrl = computed(() =>
    appUrl.value && page.url ? `${appUrl.value}${page.url}` : '',
);
const defaultOgImage = computed(() =>
    appUrl.value ? `${appUrl.value}/images/branding/hero-bg.png` : '',
);
const indexDescription =
    'Browse and explore a curated collection of war films from throughout cinema history. Filter by conflict, country, year, and tags.';

// View mode
const { isGridView, isListView, setGridView, setListView } = useViewMode();

// Infinite scroll vs pagination toggle
const useInfiniteScrollMode = useStorage('movies-infinite-scroll', true);

// Watchlisted IDs from server (for initial render and after page loads)
const watchlistedIds = computed(
    () => new Set(props.movies.watchlisted_ids ?? []),
);

function isMovieWatchlisted(movieId: number): boolean {
    return watchlistedIds.value.has(movieId);
}

// Sort options
const sortOptions: {
    value: MovieSortOption;
    label: string;
    icon: typeof Calendar;
}[] = [
    {
        value: 'release_year_desc',
        label: 'Newest First',
        icon: CalendarArrowDown,
    },
    { value: 'release_year_asc', label: 'Oldest First', icon: CalendarArrowUp },
    { value: 'title_asc', label: 'Title A-Z', icon: ArrowDownAZ },
    { value: 'title_desc', label: 'Title Z-A', icon: ArrowUpAZ },
    { value: 'created_at_desc', label: 'Recently Added', icon: Calendar },
];

const queryParams = computed(() => props.queryParams);
const search = ref(queryParams.value?.search || '');
const selectedYear = ref(queryParams.value?.year || '');
const selectedCountry = ref(queryParams.value?.country || '');
const selectedConflict = ref(queryParams.value?.conflict || '');
const selectedTag = ref(queryParams.value?.tag || '');
const selectedSort = ref<MovieSortOption>(
    (queryParams.value?.sort as MovieSortOption) || 'release_year_desc',
);
const showFilters = ref(false);
const mobileFiltersOpen = ref(false);
const isFiltering = ref(false);

// Infinite scroll state
const allMovies = ref<Movie[]>([]);
const currentPage = ref(1);
const hasMorePages = computed(
    () => currentPage.value < props.movies.meta.last_page,
);

// Initialize movies on mount and when props change
watch(
    () => props.movies,
    (newMovies) => {
        if (currentPage.value === 1) {
            allMovies.value = [...newMovies.data];
            currentPage.value = newMovies.meta.current_page;
        }
    },
    { immediate: true },
);

// Reset accumulated movies when filters change
function resetMovies() {
    allMovies.value = [...props.movies.data];
    currentPage.value = props.movies.meta.current_page;
}

// Apply enter animation only when list was replaced (filter or initial load), not when appended
const shouldAnimateCards = ref(true);

const currentSortOption = computed(() => {
    const found = sortOptions.find((opt) => opt.value === selectedSort.value);
    return found ?? sortOptions[0]!;
});

const recentDecadeStart = computed(() => {
    const years = props.filters.years;
    if (!years?.length) {
        return null;
    }
    const maxYear = Math.max(...years);
    return Math.floor(maxYear / 10) * 10;
});

const recentDecadeLabel = computed(() => {
    const start = recentDecadeStart.value;
    if (start == null) {
        return '';
    }
    return `${start}s`;
});

const debouncedFilter = useDebounceFn(() => {
    // Reset to page 1 when filters change
    currentPage.value = 1;

    router.get(
        '/movies',
        {
            search: search.value || undefined,
            year: selectedYear.value || undefined,
            country: selectedCountry.value || undefined,
            conflict: selectedConflict.value || undefined,
            tag: selectedTag.value || undefined,
            sort: selectedSort.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            onStart: () => {
                isFiltering.value = true;
            },
            onFinish: () => {
                isFiltering.value = false;
                resetMovies();
                shouldAnimateCards.value = true;
            },
        },
    );
}, 300);

watch(
    [
        search,
        selectedYear,
        selectedCountry,
        selectedConflict,
        selectedTag,
        selectedSort,
    ],
    () => {
        void debouncedFilter();
    },
);

watch(mobileFiltersOpen, (isOpen) => {
    if (!isOpen) {
        return;
    }
    showFilters.value = false;
});

const clearFilters = () => {
    search.value = '';
    selectedYear.value = '';
    selectedCountry.value = '';
    selectedConflict.value = '';
    selectedTag.value = '';
    selectedSort.value = 'release_year_desc';
};

const hasActiveFilters = () => {
    return (
        search.value ||
        selectedYear.value ||
        selectedCountry.value ||
        selectedConflict.value ||
        selectedTag.value
    );
};

type Chip = {
    key: 'year' | 'country' | 'conflict' | 'tag' | 'search';
    label: string;
};

const activeChips = computed<Chip[]>(() => {
    const chips: Chip[] = [];

    if (search.value) {
        chips.push({ key: 'search', label: `Search: ${search.value}` });
    }
    if (selectedYear.value) {
        chips.push({ key: 'year', label: `Year: ${selectedYear.value}` });
    }
    if (selectedCountry.value) {
        chips.push({
            key: 'country',
            label: `Country: ${selectedCountry.value}`,
        });
    }
    if (selectedConflict.value) {
        chips.push({
            key: 'conflict',
            label: `Conflict: ${selectedConflict.value}`,
        });
    }
    if (selectedTag.value) {
        const tagName =
            filterTags.value.find((t) => t.slug === selectedTag.value)
                ?.name ?? selectedTag.value;
        chips.push({ key: 'tag', label: `Tag: ${tagName}` });
    }

    return chips;
});

function removeChip(chip: Chip) {
    if (chip.key === 'search') {
        search.value = '';
    }
    if (chip.key === 'year') {
        selectedYear.value = '';
    }
    if (chip.key === 'country') {
        selectedCountry.value = '';
    }
    if (chip.key === 'conflict') {
        selectedConflict.value = '';
    }
    if (chip.key === 'tag') {
        selectedTag.value = '';
    }
}

// Preview modal state
const previewMovie = ref<Movie | null>(null);
const previewOpen = ref(false);

function openPreview(movie: Movie) {
    previewMovie.value = movie;
    previewOpen.value = true;
}

// Infinite scroll
const isLoadingMore = ref(false);

async function loadMoreMovies() {
    if (!hasMorePages.value || isLoadingMore.value) return;

    isLoadingMore.value = true;
    const nextPage = currentPage.value + 1;

    router.get(
        '/movies',
        {
            search: search.value || undefined,
            year: selectedYear.value || undefined,
            country: selectedCountry.value || undefined,
            conflict: selectedConflict.value || undefined,
            tag: selectedTag.value || undefined,
            sort: selectedSort.value || undefined,
            page: nextPage,
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['movies'],
            onSuccess: (page) => {
                const newMovies = (
                    page.props.movies as PaginatedMovies & {
                        watchlisted_ids?: number[];
                    }
                ).data;
                allMovies.value = [...allMovies.value, ...newMovies];
                currentPage.value = nextPage;
                shouldAnimateCards.value = false;
            },
            onFinish: () => {
                isLoadingMore.value = false;
            },
        },
    );
}

const infiniteScrollEnabled = computed(
    () => useInfiniteScrollMode.value && hasMorePages.value,
);

const { sentinelRef } = useInfiniteScroll(loadMoreMovies, {
    enabled: infiniteScrollEnabled,
});

// Movies to display (all accumulated for infinite scroll, or just current page)
const displayedMovies = computed(() => {
    if (useInfiniteScrollMode.value) {
        return allMovies.value;
    }
    return props.movies.data;
});

const firstPageLength = computed(() => props.movies.data.length);

function cardEnterClass(index: number) {
    return shouldAnimateCards.value && index < firstPageLength.value
        ? 'movie-card-enter'
        : '';
}

function cardEnterStyle(index: number) {
    return shouldAnimateCards.value && index < firstPageLength.value
        ? { '--delay': `${index * 40}ms` }
        : undefined;
}
</script>

<template>
    <PublicLayout>
        <Head title="Browse Movies">
            <meta
                head-key="description"
                name="description"
                :content="indexDescription"
            />
            <link head-key="canonical" rel="canonical" :href="canonicalUrl" />
            <meta property="og:type" content="website" />
            <meta property="og:title" content="Browse Movies" />
            <meta property="og:description" :content="indexDescription" />
            <meta property="og:image" :content="defaultOgImage" />
            <meta property="og:url" :content="canonicalUrl" />
            <meta property="og:site_name" content="Movies of War" />
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:title" content="Browse Movies" />
            <meta name="twitter:description" :content="indexDescription" />
            <meta name="twitter:image" :content="defaultOgImage" />
        </Head>

        <PublicSection
            spacing="md"
            class="relative overflow-hidden pt-10 md:pt-14 lg:pt-20"
        >
            <div
                class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_1px_1px,white_1px,transparent_0)] bg-size-[20px_20px] opacity-[0.04]"
                aria-hidden="true"
            />
            <PublicContainer class="relative flex flex-col gap-8">
                <SectionHeader
                    kicker="Browse"
                    title="War Films"
                    description="Filter by conflict, country, year, and tags—then save films you want to watch."
                />

                <!-- Quick filter pills -->
                <div
                    v-if="
                        filters.conflicts.length > 0 || filters.years.length > 0
                    "
                    aria-label="Quick filters"
                    class="scrollbar-none flex snap-x snap-mandatory items-center gap-2 overflow-x-auto pb-1 whitespace-nowrap"
                >
                    <span
                        class="shrink-0 snap-start text-xs font-medium tracking-wider text-zinc-500 uppercase"
                    >
                        Quick filters
                    </span>
                    <button
                        v-for="conflict in filters.conflicts.slice(0, 5)"
                        :key="conflict"
                        type="button"
                        :aria-pressed="selectedConflict === conflict"
                        :class="[
                            'shrink-0 snap-start rounded-full px-3 py-1.5 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600',
                            selectedConflict === conflict
                                ? 'bg-red-600 text-white'
                                : 'bg-zinc-800/80 text-zinc-300 ring-1 ring-zinc-700/70 hover:bg-zinc-800 hover:text-white',
                        ]"
                        @click="
                            selectedConflict =
                                selectedConflict === conflict ? '' : conflict
                        "
                    >
                        {{ conflict }}
                    </button>
                    <template
                        v-if="
                            filters.years.length >= 2 &&
                            recentDecadeStart != null
                        "
                    >
                        <span
                            class="mx-1 h-1 w-1 shrink-0 rounded-full bg-zinc-600"
                            aria-hidden="true"
                        />
                        <button
                            type="button"
                            :aria-pressed="
                                selectedYear === String(recentDecadeStart)
                            "
                            :class="[
                                'shrink-0 snap-start rounded-full px-3 py-1.5 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600',
                                selectedYear === String(recentDecadeStart)
                                    ? 'bg-red-600 text-white'
                                    : 'bg-zinc-800/80 text-zinc-300 ring-1 ring-zinc-700/70 hover:bg-zinc-800 hover:text-white',
                            ]"
                            @click="
                                selectedYear =
                                    selectedYear === String(recentDecadeStart)
                                        ? ''
                                        : String(recentDecadeStart)
                            "
                        >
                            {{ recentDecadeLabel }}
                        </button>
                    </template>
                </div>

                <div class="flex flex-col gap-4">
                    <nav
                        aria-label="Movie browsing controls"
                        class="flex flex-col gap-3 rounded-2xl bg-zinc-950/60 p-4 ring-1 ring-zinc-800/70 backdrop-blur-sm lg:flex-row lg:items-center lg:gap-4"
                    >
                        <div class="relative w-full lg:flex-1">
                            <Search
                                class="pointer-events-none absolute top-1/2 left-3 z-10 size-5 -translate-y-1/2 text-zinc-400"
                            />
                            <Input
                                v-model="search"
                                type="text"
                                placeholder="Search movies..."
                                class="w-full pl-10"
                            />
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <!-- Sort dropdown -->
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button
                                        variant="outline"
                                        class="gap-2"
                                        aria-label="Sort movies"
                                    >
                                        <component
                                            :is="currentSortOption.icon"
                                            class="size-4"
                                        />
                                        <span class="hidden sm:inline">{{
                                            currentSortOption.label
                                        }}</span>
                                        <ChevronDown class="size-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" class="w-48">
                                    <DropdownMenuItem
                                        v-for="option in sortOptions"
                                        :key="option.value"
                                        :class="
                                            selectedSort === option.value
                                                ? 'bg-zinc-800'
                                                : ''
                                        "
                                        @click="selectedSort = option.value"
                                    >
                                        <component
                                            :is="option.icon"
                                            class="mr-2 size-4"
                                        />
                                        {{ option.label }}
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>

                            <!-- View mode toggle -->
                            <div
                                role="group"
                                aria-label="View mode"
                                class="hidden items-center rounded-lg border border-zinc-800 p-1 sm:flex"
                            >
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="size-8"
                                    :class="
                                        isGridView
                                            ? 'bg-zinc-800 text-white'
                                            : 'text-zinc-400'
                                    "
                                    @click="setGridView"
                                >
                                    <Grid3X3 class="size-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="size-8"
                                    :class="
                                        isListView
                                            ? 'bg-zinc-800 text-white'
                                            : 'text-zinc-400'
                                    "
                                    @click="setListView"
                                >
                                    <List class="size-4" />
                                </Button>
                            </div>

                            <Button
                                variant="outline"
                                class="hidden lg:inline-flex"
                                @click="showFilters = !showFilters"
                            >
                                <Filter class="size-4" />
                                Filters
                                <span
                                    v-if="activeChips.length"
                                    class="rounded-full bg-red-600 px-2 py-0.5 text-xs text-white"
                                >
                                    {{ activeChips.length }}
                                </span>
                            </Button>

                            <Sheet v-model:open="mobileFiltersOpen">
                                <SheetTrigger as-child>
                                    <Button variant="outline" class="lg:hidden">
                                        <SlidersHorizontal class="size-4" />
                                        Filters
                                        <span
                                            v-if="activeChips.length"
                                            class="rounded-full bg-red-600 px-2 py-0.5 text-xs text-white"
                                        >
                                            {{ activeChips.length }}
                                        </span>
                                    </Button>
                                </SheetTrigger>
                                <SheetContent
                                    side="right"
                                    class="w-[320px] bg-zinc-950"
                                >
                                    <SheetHeader>
                                        <SheetTitle class="text-white">
                                            Filters
                                        </SheetTitle>
                                    </SheetHeader>
                                    <MoviesFiltersPanel
                                        class="mt-6"
                                        :years="filters.years"
                                        :countries="filters.countries"
                                        :conflicts="filters.conflicts"
                                        :tags="filterTags"
                                        :year="selectedYear"
                                        :country="selectedCountry"
                                        :conflict="selectedConflict"
                                        :tag="selectedTag"
                                        @update:year="selectedYear = $event"
                                        @update:country="
                                            selectedCountry = $event
                                        "
                                        @update:conflict="
                                            selectedConflict = $event
                                        "
                                        @update:tag="selectedTag = $event"
                                    />

                                    <div class="mt-4 flex gap-2">
                                        <Button
                                            variant="outline"
                                            class="flex-1"
                                            @click="clearFilters"
                                        >
                                            <X class="size-4" />
                                            Clear
                                        </Button>
                                        <Button
                                            class="flex-1"
                                            @click="mobileFiltersOpen = false"
                                        >
                                            Done
                                        </Button>
                                    </div>
                                </SheetContent>
                            </Sheet>

                            <Button
                                v-if="hasActiveFilters()"
                                variant="ghost"
                                class="text-zinc-300 hover:text-white"
                                @click="clearFilters"
                            >
                                <X class="size-4" />
                                Clear
                            </Button>
                        </div>
                    </nav>

                    <MoviesFiltersPanel
                        v-if="showFilters"
                        :years="filters.years"
                        :countries="filters.countries"
                        :conflicts="filters.conflicts"
                        :tags="filterTags"
                        :year="selectedYear"
                        :country="selectedCountry"
                        :conflict="selectedConflict"
                        :tag="selectedTag"
                        @update:year="selectedYear = $event"
                        @update:country="selectedCountry = $event"
                        @update:conflict="selectedConflict = $event"
                        @update:tag="selectedTag = $event"
                    />

                    <div v-if="activeChips.length" class="flex flex-wrap gap-2">
                        <FilterChip
                            v-for="chip in activeChips"
                            :key="chip.key"
                            :label="chip.label"
                            @remove="removeChip(chip)"
                        />
                    </div>

                    <div
                        class="flex items-center justify-between border-t border-zinc-800/60 pt-4"
                    >
                        <p
                            class="text-xs font-medium tracking-wide text-zinc-400 uppercase"
                        >
                            <span class="sr-only">Results count:</span>
                            {{ movies.meta?.total ?? 0 }} films
                        </p>
                        <label
                            class="flex cursor-pointer items-center gap-2 text-xs font-medium tracking-wide text-zinc-400 uppercase"
                        >
                            <input
                                v-model="useInfiniteScrollMode"
                                type="checkbox"
                                class="size-4 rounded border-zinc-700 bg-zinc-900 text-red-600 focus:ring-red-500"
                            />
                            Infinite scroll
                        </label>
                    </div>
                </div>

                <div v-if="displayedMovies && displayedMovies.length > 0">
                    <MovieGridSkeleton v-if="isFiltering" />

                    <!-- Grid view -->
                    <MovieGrid v-else-if="isGridView">
                        <MovieCard
                            v-for="(movie, index) in displayedMovies"
                            :key="movie.id"
                            :movie="movie"
                            :class="cardEnterClass(index)"
                            :style="
                                cardEnterStyle(index) as Record<string, string>
                            "
                        >
                            <template #overlay>
                                <MovieCardActions
                                    :movie="movie"
                                    :is-watchlisted="
                                        isMovieWatchlisted(movie.id)
                                    "
                                    @preview="openPreview(movie)"
                                />
                            </template>
                        </MovieCard>
                    </MovieGrid>

                    <!-- List view -->
                    <div v-else class="flex flex-col gap-3">
                        <MovieListItem
                            v-for="(movie, index) in displayedMovies"
                            :key="movie.id"
                            :movie="movie"
                            :is-watchlisted="isMovieWatchlisted(movie.id)"
                            :class="cardEnterClass(index)"
                            :style="
                                cardEnterStyle(index) as Record<string, string>
                            "
                            @preview="openPreview(movie)"
                        />
                    </div>

                    <!-- Infinite scroll sentinel -->
                    <div
                        v-if="useInfiniteScrollMode && hasMorePages"
                        ref="sentinelRef"
                        class="flex justify-center py-8"
                    >
                        <Loader2
                            v-if="isLoadingMore"
                            class="size-8 animate-spin text-zinc-500"
                        />
                    </div>

                    <!-- Traditional pagination -->
                    <div
                        v-if="
                            !useInfiniteScrollMode &&
                            movies.meta &&
                            movies.meta.last_page > 1
                        "
                        class="mt-10 flex flex-wrap items-center justify-center gap-2"
                    >
                        <Link
                            v-for="link in movies.meta.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            :class="[
                                'rounded-xl px-4 py-2 text-sm font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500',
                                link.active
                                    ? 'bg-red-600 text-white'
                                    : link.url
                                      ? 'bg-zinc-950 text-zinc-200 ring-1 ring-zinc-800/70 hover:bg-zinc-900'
                                      : 'cursor-not-allowed bg-zinc-950 text-zinc-600 ring-1 ring-zinc-800/70',
                            ]"
                            :preserve-scroll="true"
                            :preserve-state="true"
                        >
                            <!-- eslint-disable-next-line vue/no-v-html -->
                            <span v-html="link.label" />
                        </Link>
                    </div>
                </div>

                <div v-else class="empty-state-enter py-16 text-center">
                    <img
                        src="/images/illustrations/no-movies-found.png"
                        alt="No movies found"
                        class="mx-auto mb-6 h-32 w-32 opacity-50"
                    />
                    <p class="text-xl text-zinc-300">
                        No movies found matching your filters.
                    </p>
                    <Button
                        variant="outline"
                        class="mt-5"
                        @click="clearFilters"
                    >
                        Clear filters
                    </Button>
                </div>
            </PublicContainer>
        </PublicSection>

        <!-- Preview Modal -->
        <MoviePreviewDialog
            v-model:open="previewOpen"
            :movie="previewMovie"
            :is-watchlisted="
                previewMovie ? isMovieWatchlisted(previewMovie.id) : false
            "
        />
    </PublicLayout>
</template>
