<script setup lang="ts">
import type { Movie } from '@/types';

import { Link } from '@inertiajs/vue3';
import { Info, Play } from 'lucide-vue-next';

interface Props {
    movie: Movie;
    title: string;
}

const props = defineProps<Props>();

const posterImage =
    props.movie.poster_url || '/images/placeholders/poster-placeholder.png';
</script>

<template>
    <div
        class="relative overflow-hidden rounded-lg border border-zinc-800 bg-zinc-900/50"
    >
        <div class="absolute top-4 right-4 z-10">
            <span
                class="inline-flex rounded-full bg-red-600/90 px-3 py-1 text-xs font-bold tracking-wider text-white uppercase backdrop-blur-sm"
            >
                {{ title }}
            </span>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="md:col-span-1">
                <img
                    :src="posterImage"
                    :alt="movie.title"
                    class="h-full w-full object-cover md:max-h-[300px]"
                />
            </div>

            <div class="flex flex-col justify-center p-6 md:col-span-2">
                <h3 class="mb-2 text-2xl font-bold text-white">
                    {{ movie.title }}
                </h3>

                <div
                    class="mb-3 flex flex-wrap items-center gap-2 text-sm text-zinc-400"
                >
                    <span>{{ movie.release_year }}</span>
                    <span v-if="movie.runtime">• {{ movie.runtime }} min</span>
                    <span v-if="movie.country">• {{ movie.country }}</span>
                </div>

                <p class="mb-4 line-clamp-2 text-sm text-zinc-400">
                    {{ movie.synopsis }}
                </p>

                <div
                    v-if="movie.tags && movie.tags.length > 0"
                    class="mb-4 flex flex-wrap gap-1"
                >
                    <span
                        v-for="tag in movie.tags.slice(0, 3)"
                        :key="tag.id"
                        class="rounded bg-zinc-800 px-2 py-1 text-xs text-zinc-300"
                    >
                        {{ tag.name }}
                    </span>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Link
                        :href="`/movies/${movie.slug}`"
                        class="inline-flex items-center gap-1 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-red-700"
                    >
                        <Info class="size-4" />
                        Details
                    </Link>

                    <a
                        v-if="movie.trailer_url"
                        :href="movie.trailer_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1 rounded-lg border border-zinc-700 bg-zinc-800/50 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-zinc-700"
                    >
                        <Play class="size-4" />
                        Trailer
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
