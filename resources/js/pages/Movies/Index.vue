<script setup lang="ts">
import type { MovieFilters, PaginatedMovies, Tag } from '@/types';

import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { Filter, Search, SlidersHorizontal, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import MovieCard from '@/components/MovieCard.vue';
import FilterChip from '@/components/public/FilterChip.vue';
import MovieGrid from '@/components/public/MovieGrid.vue';
import MovieGridSkeleton from '@/components/public/MovieGridSkeleton.vue';
import MoviesFiltersPanel from '@/components/public/MoviesFiltersPanel.vue';
import PublicContainer from '@/components/public/PublicContainer.vue';
import PublicSection from '@/components/public/PublicSection.vue';
import SectionHeader from '@/components/public/SectionHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import PublicLayout from '@/layouts/PublicLayout.vue';

interface Props {
    filters: {
        conflicts: string[];
        countries: string[];
        tags: Tag[];
        years: number[];
    };
    movies: PaginatedMovies;
    queryParams: MovieFilters;
}

const props = defineProps<Props>();

const queryParams = computed(() => props.queryParams);
const search = ref(queryParams.value?.search || '');
const selectedYear = ref(queryParams.value?.year || '');
const selectedCountry = ref(queryParams.value?.country || '');
const selectedConflict = ref(queryParams.value?.conflict || '');
const selectedTag = ref(queryParams.value?.tag || '');
const showFilters = ref(false);
const mobileFiltersOpen = ref(false);
const isFiltering = ref(false);

const debouncedFilter = useDebounceFn(() => {
    router.get(
        '/movies',
        {
            search: search.value || undefined,
            year: selectedYear.value || undefined,
            country: selectedCountry.value || undefined,
            conflict: selectedConflict.value || undefined,
            tag: selectedTag.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            onStart: () => {
                isFiltering.value = true;
            },
            onFinish: () => {
                isFiltering.value = false;
            },
        },
    );
}, 300);

watch(
    [search, selectedYear, selectedCountry, selectedConflict, selectedTag],
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
            props.filters.tags.find((t) => t.slug === selectedTag.value)
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
</script>

<template>
    <PublicLayout>
        <Head title="Browse Movies - Movies of War" />

        <PublicSection spacing="md">
            <PublicContainer class="flex flex-col gap-8">
                <SectionHeader
                    title="Browse War Films"
                    description="Filter by conflict, country, year, and tags—then save films you want to watch."
                />

                <div class="flex flex-col gap-4">
                    <div
                        class="flex flex-col gap-3 lg:flex-row lg:items-center"
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

                        <div class="flex items-center gap-2">
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
                                        :tags="filters.tags"
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
                    </div>

                    <MoviesFiltersPanel
                        v-if="showFilters"
                        :years="filters.years"
                        :countries="filters.countries"
                        :conflicts="filters.conflicts"
                        :tags="filters.tags"
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

                    <p class="text-sm text-zinc-300">
                        {{ movies.meta?.total ?? 0 }} films
                    </p>
                </div>

                <div v-if="movies.data && movies.data.length > 0">
                    <MovieGridSkeleton v-if="isFiltering" />

                    <MovieGrid v-else>
                        <MovieCard
                            v-for="movie in movies.data"
                            :key="movie.id"
                            :movie="movie"
                        />
                    </MovieGrid>

                    <div
                        v-if="movies.meta && movies.meta.last_page > 1"
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

                <div v-else class="py-16 text-center">
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
    </PublicLayout>
</template>
