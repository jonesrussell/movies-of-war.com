<script setup lang="ts">
import type { Movie, PaginatedMovies } from '@/types/models';

import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { computed, ref, watch } from 'vue';

import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';

interface Props {
    movies: PaginatedMovies;
    queryParams: {
        search?: string;
    };
}

const props = defineProps<Props>();
usePage();

const queryParams = computed(() => props.queryParams);
const search = ref(queryParams.value?.search || '');

const debouncedSearch = useDebounceFn((searchValue: string) => {
    router.get(
        '/dashboard/movies',
        {
            search: searchValue || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
}, 300);

watch(search, (value) => {
    void debouncedSearch(value);
});

function archiveMovie(movie: Movie) {
    if (confirm(`Are you sure you want to archive "${movie.title}"?`)) {
        router.delete(`/movies/${movie.id}`, {
            preserveScroll: true,
        });
    }
}

function publishMovie(movie: Movie) {
    router.post(
        `/movies/${movie.id}/publish`,
        {},
        {
            preserveScroll: true,
        },
    );
}

function unpublishMovie(movie: Movie) {
    router.post(
        `/movies/${movie.id}/unpublish`,
        {},
        {
            preserveScroll: true,
        },
    );
}
</script>

<template>
    <AppSidebarLayout>
        <Head title="Manage Movies - Admin" />

        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white">Manage Movies</h1>
                    <p class="mt-2 text-zinc-400">
                        {{ movies?.meta?.total ?? 0 }} total movies
                    </p>
                </div>
                <Link
                    :href="'/movies/create'"
                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                >
                    Add New Movie
                </Link>
            </div>

            <!-- Search -->
            <div class="mb-6">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search movies..."
                    class="w-full rounded-lg border-zinc-700 bg-zinc-900 px-4 py-2 text-white placeholder-zinc-500 focus:border-red-500 focus:ring-red-500"
                />
            </div>

            <!-- Movies Table -->
            <div class="overflow-hidden rounded-lg bg-zinc-900">
                <table class="min-w-full divide-y divide-zinc-800">
                    <thead class="bg-zinc-950">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-zinc-400 uppercase"
                            >
                                Title
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-zinc-400 uppercase"
                            >
                                Year
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-zinc-400 uppercase"
                            >
                                Tags
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-zinc-400 uppercase"
                            >
                                Status
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
                                colspan="5"
                                class="px-6 py-12 text-center text-zinc-400"
                            >
                                No movies found.
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
                                        class="h-12 w-8 rounded bg-zinc-800"
                                        v-else
                                    />
                                    <div>
                                        <div class="font-medium text-white">
                                            {{ movie.title }}
                                        </div>
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
                            <td class="px-6 py-4">
                                <span
                                    :class="[
                                        'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                        movie.status === 'published'
                                            ? 'bg-green-900/50 text-green-300'
                                            : movie.status === 'draft'
                                              ? 'bg-yellow-900/50 text-yellow-300'
                                              : 'bg-zinc-800 text-zinc-400',
                                    ]"
                                >
                                    {{
                                        movie.status === 'published'
                                            ? 'Published'
                                            : movie.status === 'draft'
                                              ? 'Draft'
                                              : 'Archived'
                                    }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Link
                                        :href="`/movies/${movie.id}/edit`"
                                        class="text-red-500 hover:text-red-400"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        v-if="movie.status !== 'published'"
                                        @click="publishMovie(movie)"
                                        class="text-green-500 hover:text-green-400"
                                    >
                                        Publish
                                    </button>
                                    <button
                                        v-if="movie.status === 'published'"
                                        @click="unpublishMovie(movie)"
                                        class="text-yellow-500 hover:text-yellow-400"
                                    >
                                        Unpublish
                                    </button>
                                    <button
                                        v-if="movie.status !== 'archived'"
                                        @click="archiveMovie(movie)"
                                        class="text-zinc-400 hover:text-white"
                                    >
                                        Archive
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="movies?.meta?.last_page && movies?.meta?.last_page > 1"
                class="mt-6 flex justify-center gap-2"
            >
                <Link
                    v-for="page in movies?.meta?.links"
                    :key="page.label"
                    :href="page.url || '#'"
                    :class="[
                        'rounded px-3 py-2 text-sm',
                        page.active
                            ? 'bg-red-600 text-white'
                            : page.url
                              ? 'bg-zinc-800 text-zinc-300 hover:bg-zinc-700'
                              : 'cursor-not-allowed bg-zinc-900 text-zinc-600',
                    ]"
                >
                    <!-- eslint-disable-next-line vue/no-v-html -->
                    <span v-html="page.label" />
                </Link>
            </div>
        </div>
    </AppSidebarLayout>
</template>
