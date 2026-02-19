<script setup lang="ts">
import type { Movie } from '@/types';

import { Link } from '@inertiajs/vue3';
import { Info, Play } from 'lucide-vue-next';

import { Poster, StarRating } from '@/components/primitives';
import UpcomingBadge from '@/components/UpcomingBadge.vue';

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
        class="featured-movie relative overflow-hidden rounded-md border border-[--intel-border] bg-[--intel-bg-surface]"
        style="container-type: inline-size"
    >
        <div class="absolute top-4 right-4 z-10">
            <span
                class="inline-flex items-center font-[family-name:var(--font-mono-display)] text-xs font-semibold tracking-[0.2em] text-blue-500 uppercase"
            >
                <span
                    class="mr-3 inline-block h-px w-6 bg-blue-500 align-middle"
                ></span>
                INCOMING // {{ title.toUpperCase() }}
            </span>
        </div>

        <div class="featured-movie-grid grid grid-cols-1 md:grid-cols-12">
            <div
                class="featured-movie-content order-1 flex flex-col justify-center p-6 md:col-span-8"
            >
                <h3 class="mb-2 text-2xl font-bold text-[--intel-text-primary]">
                    {{ movie.title }}
                </h3>

                <div
                    class="mb-3 flex flex-wrap items-center gap-2 font-[family-name:var(--font-mono-display)] text-sm text-[--intel-text-body]"
                >
                    <span>{{ movie.release_year }}</span>
                    <span v-if="movie.runtime">// {{ movie.runtime }} min</span>
                    <span v-if="movie.country">// {{ movie.country }}</span>
                </div>

                <p class="mb-4 line-clamp-2 text-sm text-[--intel-text-body]">
                    {{ movie.synopsis }}
                </p>

                <div
                    v-if="movie.tags && movie.tags.length > 0"
                    class="mb-4 flex flex-wrap gap-1"
                >
                    <span
                        v-for="tag in movie.tags.slice(0, 3)"
                        :key="tag.id"
                        class="rounded-sm border border-[--intel-border] bg-[--intel-bg-elevated] px-2 py-1 font-[family-name:var(--font-mono-display)] text-xs text-[--intel-text-body]"
                    >
                        {{ tag.name }}
                    </span>
                </div>

                <div
                    v-if="movie.user_review"
                    class="mb-4 flex flex-wrap items-center gap-2 text-sm text-[--intel-text-body]"
                >
                    <span class="text-[--intel-text-muted]">Your rating:</span>
                    <StarRating
                        :rating="movie.user_review.rating"
                        :max-stars="4"
                        size="sm"
                    />
                    <Link
                        :href="`/movies/${movie.slug}#reviews`"
                        class="font-medium text-blue-500 transition-colors hover:text-blue-400"
                    >
                        Your review
                    </Link>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Link
                        :href="`/movies/${movie.slug}`"
                        class="inline-flex items-center gap-1 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-blue-700"
                    >
                        <Info class="size-4" />
                        Details
                    </Link>

                    <a
                        v-if="movie.trailer_url"
                        :href="movie.trailer_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1 rounded-md border border-[--intel-border] bg-[--intel-bg-elevated] px-4 py-2 text-sm font-semibold text-[--intel-text-primary] transition-colors hover:bg-[--intel-bg-surface]"
                    >
                        <Play class="size-4" />
                        Trailer
                    </a>
                </div>
            </div>

            <div
                class="featured-movie-poster order-2 flex items-center justify-center border-t border-[--intel-border] bg-[--intel-bg-elevated]/20 p-6 md:col-span-4 md:border-t-0 md:border-l"
            >
                <div class="aspect-[2/3] w-full max-w-[260px]">
                    <Poster
                        :src="posterImage"
                        :alt="movie.title"
                        :poster-path="movie.poster_path"
                        context="grid"
                        aspect-ratio="2/3"
                        class="rounded-sm"
                    >
                        <div
                            v-if="movie.is_upcoming"
                            class="absolute top-3 right-3 z-10"
                        >
                            <UpcomingBadge />
                        </div>
                    </Poster>
                </div>
            </div>
        </div>
    </div>
</template>
