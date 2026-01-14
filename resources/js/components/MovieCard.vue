<script setup lang="ts">
import type { Movie } from '@/types';

import { Link } from '@inertiajs/vue3';

interface Props {
    movie: Movie;
}

const props = defineProps<Props>();

const posterImage =
    props.movie.poster_url || '/images/placeholders/poster-placeholder.png';
</script>

<template>
    <div class="group flex flex-col gap-2">
        <Link
            :href="`/movies/${movie.slug}`"
            class="relative block overflow-hidden rounded-xl bg-zinc-950 ring-1 ring-zinc-800/70 transition duration-300 hover:scale-[1.01] focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
        >
            <div class="aspect-[2/3] overflow-hidden">
                <img
                    :src="posterImage"
                    :alt="movie.title"
                    class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]"
                    loading="lazy"
                    decoding="async"
                />
            </div>

            <div
                class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"
            >
                <div class="absolute right-0 bottom-0 left-0 p-4">
                    <h3 class="mb-2 text-lg font-bold text-white">
                        {{ movie.title }}
                    </h3>

                    <div
                        class="mb-2 flex items-center gap-2 text-sm text-zinc-300"
                    >
                        <span>{{ movie.release_year }}</span>
                        <span
                            v-if="movie.runtime"
                            class="flex items-center gap-1"
                        >
                            • {{ movie.runtime }} min
                        </span>
                        <span v-if="movie.country">• {{ movie.country }}</span>
                    </div>

                    <p
                        v-if="movie.synopsis"
                        class="line-clamp-2 text-sm text-zinc-400"
                    >
                        {{ movie.synopsis }}
                    </p>

                    <div
                        v-if="movie.tags && movie.tags.length > 0"
                        class="mt-2 flex flex-wrap gap-1"
                    >
                        <span
                            v-for="tag in movie.tags.slice(0, 3)"
                            :key="tag.id"
                            class="rounded bg-zinc-800/80 px-2 py-0.5 text-xs text-zinc-300"
                        >
                            {{ tag.name }}
                        </span>
                    </div>
                </div>
            </div>

            <div
                v-if="movie.is_upcoming"
                class="absolute top-2 right-2 rounded bg-red-600 px-2 py-1 text-xs font-semibold text-white"
            >
                Coming Soon
            </div>
        </Link>

        <div v-if="$slots.actions">
            <slot name="actions" />
        </div>
    </div>
</template>
