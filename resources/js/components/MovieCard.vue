<script setup lang="ts">
import type { Movie } from '@/types';

import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import { Poster } from '@/components/primitives';

interface Props {
    movie: Movie;
}

const props = defineProps<Props>();

const displayedTags = computed(() => {
    const tags = props.movie.tags;
    if (!tags || !Array.isArray(tags) || typeof tags.slice !== 'function') {
        return [];
    }
    return tags.slice(0, 3);
});
</script>

<template>
    <article class="group flex flex-col gap-2">
        <div class="flex flex-col gap-2">
            <Link
                :href="`/movies/${movie.slug}`"
                :aria-label="`View details for ${movie.title} (${movie.release_year})`"
                class="relative block overflow-hidden rounded-xl ring-1 ring-zinc-800/70 transition-all duration-300 [transition-timing-function:var(--ease-cinematic)] hover:scale-[1.01] hover:shadow-xl hover:shadow-black/20 hover:ring-zinc-700/70 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
            >
                <Poster
                    :src="movie.poster_url"
                    :alt="movie.title"
                    :poster-path="movie.poster_path"
                    context="grid"
                    aspect-ratio="2/3"
                    class="transition-transform duration-500 [transition-timing-function:var(--ease-smooth-out)] group-hover:scale-[1.03]"
                >
                    <!-- Upcoming badge -->
                    <div
                        v-if="movie.is_upcoming"
                        class="absolute top-3 right-3 z-10 rounded-full bg-red-600 px-3 py-1 text-xs font-bold tracking-wide text-white uppercase shadow-lg"
                    >
                        Coming Soon
                    </div>
                </Poster>
            </Link>

            <!-- Movie details - always visible -->
            <div class="px-1">
                <h3 class="mb-1 truncate text-sm font-semibold text-white">
                    <Link
                        :href="`/movies/${movie.slug}`"
                        class="transition-colors hover:text-red-500"
                    >
                        {{ movie.title }}
                    </Link>
                </h3>

                <div class="flex items-center gap-2 text-xs text-zinc-300">
                    <span>{{ movie.release_year }}</span>
                    <template v-if="movie.runtime">
                        <span class="text-zinc-500">•</span>
                        <span>{{ movie.runtime }} min</span>
                    </template>
                    <template v-if="movie.country">
                        <span class="text-zinc-500">•</span>
                        <span>{{ movie.country }}</span>
                    </template>
                </div>

                <div
                    v-if="displayedTags.length > 0"
                    class="mt-2 flex flex-wrap gap-1"
                >
                    <span
                        v-for="tag in displayedTags"
                        :key="tag.id"
                        class="rounded-full bg-zinc-800 px-2 py-0.5 text-xs text-zinc-300"
                    >
                        {{ tag.name }}
                    </span>
                </div>
            </div>
        </div>

        <div v-if="$slots.actions">
            <slot name="actions" />
        </div>
    </article>
</template>
