<script setup lang="ts">
import type { Movie } from '@/types';

import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import { GradientOverlay, Poster } from '@/components/primitives';

interface Props {
    movie: Movie;
}

const props = defineProps<Props>();

const displayedTags = computed(() => props.movie.tags?.slice(0, 3) ?? []);
</script>

<template>
    <article class="group flex flex-col gap-2">
        <Link
            :href="`/movies/${movie.slug}`"
            :aria-label="`View details for ${movie.title} (${movie.release_year})`"
            class="relative block overflow-hidden rounded-xl ring-1 ring-zinc-800/70 transition-all duration-300 [transition-timing-function:var(--ease-cinematic)] hover:scale-[1.01] hover:shadow-xl hover:shadow-black/20 hover:ring-zinc-700/70 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
        >
            <Poster
                :src="movie.poster_url"
                :alt="movie.title"
                aspect-ratio="2/3"
                class="transition-transform duration-500 [transition-timing-function:var(--ease-smooth-out)] group-hover:scale-[1.03]"
            >
                <!-- Hover overlay -->
                <div
                    class="absolute inset-0 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                >
                    <GradientOverlay direction="to-t" intensity="medium" />

                    <div
                        class="absolute right-0 bottom-0 left-0 translate-y-2 p-4 transition-transform duration-300 group-hover:translate-y-0"
                    >
                        <h3
                            class="mb-2 line-clamp-2 text-lg font-bold text-white"
                        >
                            {{ movie.title }}
                        </h3>

                        <div
                            class="mb-2 flex items-center gap-2 text-sm text-zinc-300"
                        >
                            <span>{{ movie.release_year }}</span>
                            <template v-if="movie.runtime">
                                <span class="text-zinc-600">|</span>
                                <span>{{ movie.runtime }} min</span>
                            </template>
                            <template v-if="movie.country">
                                <span class="text-zinc-600">|</span>
                                <span>{{ movie.country }}</span>
                            </template>
                        </div>

                        <p
                            v-if="movie.synopsis"
                            class="line-clamp-2 text-sm text-zinc-400"
                        >
                            {{ movie.synopsis }}
                        </p>

                        <div
                            v-if="displayedTags.length > 0"
                            class="mt-3 flex flex-wrap gap-1.5"
                        >
                            <span
                                v-for="tag in displayedTags"
                                :key="tag.id"
                                class="rounded-full bg-zinc-800/80 px-2.5 py-0.5 text-xs font-medium text-zinc-300 backdrop-blur-sm"
                            >
                                {{ tag.name }}
                            </span>
                        </div>
                    </div>
                </div>
            </Poster>

            <!-- Upcoming badge -->
            <div
                v-if="movie.is_upcoming"
                class="absolute top-3 right-3 rounded-full bg-red-600 px-3 py-1 text-xs font-bold tracking-wide text-white uppercase shadow-lg"
            >
                Coming Soon
            </div>
        </Link>

        <div v-if="$slots.actions">
            <slot name="actions" />
        </div>
    </article>
</template>
