<script setup lang="ts">
import type { Movie, PaginatedMovies, Tag } from '@/types/models';

import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { ChevronDown, ChevronUp, Edit, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import Pagination from '@/components/Pagination.vue';
import { Input } from '@/components/ui/input';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';

const PER_PAGE_OPTIONS = [10, 20, 50, 100] as const;

interface Props {
    movies: PaginatedMovies;
    tags?: Tag[];
    queryParams: {
        search?: string;
        sort?: string;
        per_page?: number;
        tag?: string;
    };
}

const props = defineProps<Props>();
usePage();

const queryParams = computed(() => props.queryParams ?? {});
const search = ref(queryParams.value?.search ?? '');
const tagFilter = ref(queryParams.value?.tag ?? '');
const perPage = ref(queryParams.value?.per_page ?? 20);
const currentSort = computed(() => queryParams.value?.sort ?? 'updated_at_desc');

function applyFilters(updates: Record<string, string | number | undefined> = {}) {
    const resetPage = ['sort', 'per_page', 'tag'].some((k) => updates[k] !== undefined);
    router.get('/dashboard/movies/archived', {
        search: search.value || undefined,
        sort: currentSort.value || undefined,
        per_page: perPage.value,
        tag: tagFilter.value || undefined,
        ...updates,
        ...(resetPage ? { page: 1 } : {}),
    } as Record<string, string | number | undefined>, {
        preserveState: true,
        preserveScroll: true,
    });
}

const debouncedSearch = useDebounceFn((searchValue: string) => {
    applyFilters({ search: searchValue || undefined });
}, 300);

watch(search, (value) => {
    void debouncedSearch(value);
});

function applySort(column: string) {
    const next =
        currentSort.value === `${column}_asc`
            ? `${column}_desc`
            : currentSort.value === `${column}_desc`
              ? 'updated_at_desc'
              : `${column}_asc`;
    applyFilters({ sort: next });
}

function sortDirection(key: string): 'asc' | 'desc' | null {
    if (currentSort.value === `${key}_asc`) return 'asc';
    if (currentSort.value === `${key}_desc`) return 'desc';
    return null;
}

function setTag(t: string) {
    tagFilter.value = t;
    applyFilters({ tag: t || undefined });
}

function setPerPage(n: number) {
    perPage.value = n;
    applyFilters({ per_page: n });
}

const paginationSummary = computed(() => {
    const m = props.movies?.meta;
    if (!m || m.total === 0) return null;
    const from = m.from ?? 0;
    const to = m.to ?? 0;
    const total = m.total;
    return `Showing ${from}–${to} of ${total}`;
});

function deleteMovie(movie: Movie) {
    if (
        confirm(
            `Are you sure you want to permanently delete "${movie.title}"? This action cannot be undone.`,
        )
    ) {
        router.delete(`/movies/${movie.id}`, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <AppSidebarLayout>
        <Head title="Archived Movies - Admin" />

        <div class="w-full px-4 py-12 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-white">Archived Movies</h1>
                <p class="mt-2 text-zinc-400">
                    {{ movies?.meta?.total ?? 0 }} archived movies
                </p>
            </div>

            <!-- Search and filters -->
            <div class="mb-6 flex flex-col gap-4">
                <Input
                    v-model="search"
                    type="text"
                    placeholder="Search archived movies..."
                    class="w-full"
                />
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-2">
                        <label for="archived-tag-filter" class="text-sm text-zinc-400">Tag</label>
                        <select
                            id="archived-tag-filter"
                            :value="tagFilter"
                            class="rounded-lg border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-white focus:border-red-500 focus:ring-red-500"
                            @change="setTag(($event.target as HTMLSelectElement).value)"
                        >
                            <option value="">All tags</option>
                            <option
                                v-for="t in (tags ?? [])"
                                :key="t.id"
                                :value="t.slug ?? t.id"
                            >
                                {{ t.name }}
                            </option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <label for="archived-per-page" class="text-sm text-zinc-400">Per page</label>
                        <select
                            id="archived-per-page"
                            :value="perPage"
                            class="rounded-lg border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-white focus:border-red-500 focus:ring-red-500"
                            @change="setPerPage(Number(($event.target as HTMLSelectElement).value))"
                        >
                            <option
                                v-for="n in PER_PAGE_OPTIONS"
                                :key="n"
                                :value="n"
                            >
                                {{ n }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Pagination summary -->
            <div
                v-if="paginationSummary"
                class="mb-2 text-sm text-zinc-400"
            >
                {{ paginationSummary }}
            </div>

            <!-- Movies - Mobile Card View -->
            <div class="block space-y-4 md:hidden">
                <div
                    v-if="movies?.data?.length === 0"
                    class="rounded-lg bg-zinc-900 p-8 text-center text-zinc-400"
                >
                    No archived movies found.
                </div>
                <div
                    v-for="movie in movies?.data"
                    :key="movie.id"
                    class="rounded-lg bg-zinc-900 p-4"
                >
                    <div class="flex items-start gap-3">
                        <img
                            v-if="movie.poster_url"
                            :src="movie.poster_url"
                            :alt="movie.title"
                            class="h-16 w-11 shrink-0 rounded object-cover"
                        />
                        <div
                            v-else
                            class="h-16 w-11 shrink-0 rounded bg-zinc-800"
                        />
                        <div class="min-w-0 flex-1">
                            <Link
                                :href="`/dashboard/movies/${movie.id}`"
                                class="font-medium text-white transition-colors hover:text-red-500"
                            >
                                {{ movie.title }}
                            </Link>
                            <div class="mt-1 text-sm text-zinc-400">
                                {{ movie.release_year }}
                                <span v-if="movie.country">
                                    • {{ movie.country }}</span
                                >
                            </div>
                            <div
                                v-if="movie.tags && movie.tags.length > 0"
                                class="mt-2 flex flex-wrap gap-1"
                            >
                                <span
                                    v-for="tag in movie.tags.slice(0, 3)"
                                    :key="tag.id"
                                    class="rounded bg-zinc-800 px-2 py-0.5 text-xs text-zinc-300"
                                >
                                    {{ tag.name }}
                                </span>
                                <span
                                    v-if="movie.tags.length > 3"
                                    class="rounded bg-zinc-800 px-2 py-0.5 text-xs text-zinc-400"
                                >
                                    +{{ movie.tags.length - 3 }}
                                </span>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <Link
                                    :href="`/movies/${movie.id}/edit`"
                                    class="inline-flex items-center justify-center rounded-lg bg-zinc-800 p-2 text-red-500 transition-colors hover:bg-zinc-700 hover:text-red-400"
                                    title="Edit"
                                >
                                    <Edit class="size-4" />
                                </Link>
                                <button
                                    @click="deleteMovie(movie)"
                                    class="inline-flex items-center justify-center rounded-lg bg-red-600/20 p-2 text-red-500 transition-colors hover:bg-red-600/30"
                                    title="Delete"
                                >
                                    <Trash2 class="size-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Movies Table - Desktop View -->
            <div class="hidden overflow-hidden rounded-lg bg-zinc-900 md:block">
                <table class="min-w-full divide-y divide-zinc-800">
                    <thead class="bg-zinc-950">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-zinc-400 uppercase"
                            >
                                <button
                                    type="button"
                                    class="flex items-center gap-1 hover:text-white"
                                    @click="applySort('title')"
                                >
                                    Title
                                    <ChevronUp
                                        v-if="sortDirection('title') === 'asc'"
                                        class="size-4"
                                    />
                                    <ChevronDown
                                        v-else-if="sortDirection('title') === 'desc'"
                                        class="size-4"
                                    />
                                </button>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-zinc-400 uppercase"
                            >
                                <button
                                    type="button"
                                    class="flex items-center gap-1 hover:text-white"
                                    @click="applySort('release_year')"
                                >
                                    Year
                                    <ChevronUp
                                        v-if="sortDirection('release_year') === 'asc'"
                                        class="size-4"
                                    />
                                    <ChevronDown
                                        v-else-if="sortDirection('release_year') === 'desc'"
                                        class="size-4"
                                    />
                                </button>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-zinc-400 uppercase"
                            >
                                Tags
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium tracking-wider text-zinc-400 uppercase"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-800">
                        <tr
                            v-if="movies?.data?.length === 0"
                            class="hover:bg-zinc-800/50"
                        >
                            <td
                                colspan="4"
                                class="px-6 py-12 text-center text-zinc-400"
                            >
                                No archived movies found.
                            </td>
                        </tr>
                        <tr
                            v-for="movie in movies?.data"
                            :key="movie.id"
                            class="hover:bg-zinc-800/50"
                        >
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <img
                                        v-if="movie.poster_url"
                                        :src="movie.poster_url"
                                        :alt="movie.title"
                                        class="h-12 w-8 rounded object-cover"
                                    />
                                    <div
                                        v-else
                                        class="h-12 w-8 rounded bg-zinc-800"
                                    />
                                    <div>
                                        <Link
                                            :href="`/dashboard/movies/${movie.id}`"
                                            class="font-medium text-white transition-colors hover:text-red-500"
                                        >
                                            {{ movie.title }}
                                        </Link>
                                        <div class="text-sm text-zinc-400">
                                            {{ movie.country }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-zinc-300">
                                {{ movie.release_year }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        v-for="tag in movie.tags?.slice(0, 3)"
                                        :key="tag.id"
                                        class="rounded bg-zinc-800 px-2 py-1 text-xs text-zinc-300"
                                    >
                                        {{ tag.name }}
                                    </span>
                                    <span
                                        v-if="
                                            movie.tags && movie.tags.length > 3
                                        "
                                        class="rounded bg-zinc-800 px-2 py-1 text-xs text-zinc-400"
                                    >
                                        +{{ movie.tags.length - 3 }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Link
                                        :href="`/movies/${movie.id}/edit`"
                                        class="inline-flex items-center justify-center rounded-lg bg-zinc-800 p-2 text-red-500 transition-colors hover:bg-zinc-700 hover:text-red-400"
                                        title="Edit"
                                    >
                                        <Edit class="size-4" />
                                    </Link>
                                    <button
                                        @click="deleteMovie(movie)"
                                        class="inline-flex items-center justify-center rounded-lg bg-red-600/20 p-2 text-red-500 transition-colors hover:bg-red-600/30"
                                        title="Delete"
                                    >
                                        <Trash2 class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <Pagination
                v-if="movies?.meta"
                :meta="movies.meta"
            />
        </div>
    </AppSidebarLayout>
</template>
