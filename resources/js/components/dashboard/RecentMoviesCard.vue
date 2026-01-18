<script setup lang="ts">
import type { Movie } from '@/types';

import { Link } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';

interface Props {
    movies: Movie[];
    title?: string;
}

withDefaults(defineProps<Props>(), {
    title: 'Recent Movies',
});
</script>

<template>
    <div class="rounded-xl bg-zinc-900 p-5 ring-1 ring-zinc-800/70">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold text-white">{{ title }}</h3>
            <Link
                href="/movies"
                class="flex items-center gap-1 text-sm font-medium text-red-500 transition-colors hover:text-red-400"
            >
                View all
                <ArrowRight class="size-3.5" />
            </Link>
        </div>

        <div
            v-if="movies.length > 0"
            class="mt-4 grid grid-cols-3 gap-2 sm:grid-cols-6"
        >
            <Link
                v-for="movie in movies"
                :key="movie.id"
                :href="`/movies/${movie.slug}`"
                class="group relative aspect-[2/3] overflow-hidden rounded-lg bg-zinc-800"
            >
                <img
                    v-if="movie.poster_url"
                    :src="movie.poster_url"
                    :alt="movie.title"
                    class="size-full object-cover transition-transform duration-300 group-hover:scale-105"
                    loading="lazy"
                />
                <div
                    v-else
                    class="flex size-full items-center justify-center bg-zinc-800"
                >
                    <span class="text-xs text-zinc-500">No poster</span>
                </div>
                <div
                    class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/0 to-transparent opacity-0 transition-opacity group-hover:opacity-100"
                />
                <div
                    class="absolute inset-x-0 bottom-0 translate-y-2 p-2 opacity-0 transition-all group-hover:translate-y-0 group-hover:opacity-100"
                >
                    <p
                        class="truncate text-xs font-medium text-white"
                        :title="movie.title"
                    >
                        {{ movie.title }}
                    </p>
                    <p class="text-xs text-zinc-400">
                        {{ movie.release_year }}
                    </p>
                </div>
            </Link>
        </div>

        <div
            v-else
            class="mt-4 flex h-32 items-center justify-center rounded-lg bg-zinc-800/50"
        >
            <p class="text-sm text-zinc-500">No movies yet</p>
        </div>
    </div>
</template>
