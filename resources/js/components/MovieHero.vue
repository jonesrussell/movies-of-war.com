<script setup lang="ts">
import type { Movie, Review } from '@/types';

import { Link } from '@inertiajs/vue3';
import { ArrowRight, Info, Play } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

import {
    DotPattern,
    GradientOverlay,
    Poster,
    StarRating,
} from '@/components/primitives';
import PublicContainer from '@/components/public/PublicContainer.vue';
import {
    extractPosterPath,
    getTmdbPosterSizes,
    getTmdbPosterSrcset,
    isTmdbImageUrl,
} from '@/utils/image';

interface Props {
    movie: Movie;
    title?: string;
    subtitle?: string;
    /** Curator's review for Pick of the Week: shows rating, excerpt, and link to full review */
    review?: Pick<Review, 'rating' | 'content_excerpt'> | null;
}

const props = defineProps<Props>();

const posterImage = computed(
    () =>
        props.movie.poster_url || '/images/placeholders/poster-placeholder.png',
);

const tmdbPosterPath = computed(() => {
    if (props.movie.poster_path) {
        return props.movie.poster_path;
    }

    if (isTmdbImageUrl(props.movie.poster_url)) {
        return extractPosterPath(props.movie.poster_url);
    }

    return null;
});

const backgroundWebpSrcset = computed(() => {
    if (!tmdbPosterPath.value) {
        return '';
    }

    return getTmdbPosterSrcset(tmdbPosterPath.value, 'hero', true);
});

const backgroundJpegSrcset = computed(() => {
    if (!tmdbPosterPath.value) {
        return '';
    }

    return getTmdbPosterSrcset(tmdbPosterPath.value, 'hero', false);
});

const backgroundSizes = computed(() => {
    if (!tmdbPosterPath.value) {
        return undefined;
    }

    return getTmdbPosterSizes('hero');
});

const useResponsiveBackground = computed(() => {
    return Boolean(
        tmdbPosterPath.value &&
        (backgroundWebpSrcset.value || backgroundJpegSrcset.value),
    );
});

// Entrance animation state
const isVisible = ref(false);

onMounted(() => {
    requestAnimationFrame(() => {
        isVisible.value = true;
    });
});
</script>

<template>
    <section
        data-testid="movie-hero"
        class="relative overflow-hidden bg-zinc-950"
    >
        <!-- Background layers -->
        <div class="absolute inset-0">
            <!-- Blurred poster background with scale animation -->
            <picture v-if="useResponsiveBackground">
                <source
                    v-if="backgroundWebpSrcset"
                    type="image/webp"
                    :srcset="backgroundWebpSrcset"
                    :sizes="backgroundSizes"
                />
                <source
                    v-if="backgroundJpegSrcset"
                    type="image/jpeg"
                    :srcset="backgroundJpegSrcset"
                    :sizes="backgroundSizes"
                />
                <img
                    :src="posterImage"
                    :alt="movie.title"
                    class="h-full w-full scale-110 object-cover opacity-25 blur-xl transition-transform duration-[2000ms] [transition-timing-function:var(--ease-smooth-out)]"
                    :class="isVisible ? 'scale-100' : 'scale-110'"
                    fetchpriority="high"
                    decoding="async"
                />
            </picture>
            <img
                v-else
                :src="posterImage"
                :alt="movie.title"
                class="h-full w-full scale-110 object-cover opacity-25 blur-xl transition-transform duration-[2000ms] [transition-timing-function:var(--ease-smooth-out)]"
                :class="isVisible ? 'scale-100' : 'scale-110'"
                fetchpriority="high"
                decoding="async"
            />

            <!-- Multiple gradient layers for depth -->
            <GradientOverlay direction="to-r" intensity="heavy" />
            <GradientOverlay direction="to-t" intensity="medium" />
            <div class="absolute inset-0 bg-zinc-950/20" />

            <!-- Film texture pattern -->
            <DotPattern size="md" :opacity="0.06" />
        </div>

        <PublicContainer as="div" class="relative py-14 sm:py-16 lg:py-20">
            <div class="grid gap-8 lg:grid-cols-12">
                <!-- Poster with entrance animation -->
                <div
                    class="transition-all delay-150 duration-700 [transition-timing-function:var(--ease-smooth-out)] lg:col-span-4"
                    :class="
                        isVisible
                            ? 'translate-y-0 opacity-100'
                            : 'translate-y-8 opacity-0'
                    "
                >
                    <Poster
                        :src="posterImage"
                        :alt="movie.title"
                        :poster-path="tmdbPosterPath"
                        context="hero"
                        aspect-ratio="2/3"
                        :priority="true"
                        class="rounded-2xl shadow-2xl ring-1 ring-white/10 transition-all duration-300 hover:ring-white/20"
                    />
                </div>

                <!-- Content with entrance animation -->
                <div
                    class="flex flex-col justify-center transition-all delay-300 duration-700 [transition-timing-function:var(--ease-smooth-out)] lg:col-span-8 lg:pr-8"
                    :class="
                        isVisible
                            ? 'translate-y-0 opacity-100'
                            : 'translate-y-8 opacity-0'
                    "
                >
                    <div
                        v-if="subtitle"
                        class="mb-2 text-xs font-semibold tracking-[0.25em] text-red-500 uppercase"
                    >
                        {{ subtitle }}
                    </div>

                    <h1
                        class="mb-4 font-bold tracking-tight text-balance text-white"
                        style="font-size: clamp(2rem, 4vw + 1rem, 3.75rem)"
                    >
                        {{ movie.title }}
                    </h1>

                    <div
                        class="mb-6 flex flex-wrap items-center gap-4 text-sm text-zinc-300"
                    >
                        <span class="text-lg font-semibold">{{
                            movie.release_year
                        }}</span>
                        <span
                            v-if="movie.runtime"
                            class="flex items-center gap-1"
                        >
                            <span class="text-zinc-600">|</span>
                            {{ movie.runtime }} min
                        </span>
                        <span
                            v-if="movie.conflict"
                            class="rounded-full bg-zinc-900/80 px-4 py-1.5 text-xs font-semibold ring-1 ring-zinc-800/70 backdrop-blur-sm"
                        >
                            {{ movie.conflict }}
                        </span>
                        <StarRating
                            v-if="review"
                            :rating="review.rating"
                            :max-stars="4"
                            size="md"
                        />
                    </div>

                    <p
                        class="mb-8 line-clamp-4 text-lg leading-relaxed text-zinc-300"
                    >
                        {{ movie.synopsis }}
                    </p>

                    <div
                        v-if="review?.content_excerpt"
                        class="mb-8 rounded-lg border border-zinc-800/80 bg-zinc-900/50 p-6"
                    >
                        <p class="mb-4 line-clamp-3 text-zinc-300">
                            {{ review.content_excerpt }}
                        </p>
                        <Link
                            :href="`/movies/${movie.slug}#curator-review-heading`"
                            class="inline-flex items-center gap-1.5 text-sm font-semibold text-red-500 transition-colors hover:text-red-400"
                        >
                            Read full review
                            <ArrowRight class="size-4" />
                        </Link>
                    </div>

                    <div
                        v-if="movie.tags && movie.tags.length > 0"
                        class="mb-8 flex flex-wrap gap-2"
                    >
                        <span
                            v-for="tag in movie.tags"
                            :key="tag.id"
                            class="rounded-full bg-zinc-900/80 px-4 py-1.5 text-sm text-zinc-300 ring-1 ring-zinc-800/70 backdrop-blur-sm transition-colors hover:bg-zinc-800"
                        >
                            {{ tag.name }}
                        </span>
                    </div>

                    <div class="flex flex-wrap gap-4">
                        <Link
                            :href="`/movies/${movie.slug}`"
                            class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-red-600/20 transition-all hover:bg-red-700 hover:shadow-red-600/30 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                        >
                            <Info class="size-5" />
                            More Details
                        </Link>

                        <a
                            v-if="movie.trailer_url"
                            :href="movie.trailer_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 rounded-xl bg-zinc-900/80 px-6 py-3.5 text-sm font-semibold text-white ring-1 ring-zinc-800/70 backdrop-blur-sm transition-all hover:bg-zinc-800 hover:ring-zinc-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                        >
                            <Play class="size-5" />
                            Watch Trailer
                        </a>
                    </div>
                </div>
            </div>
        </PublicContainer>
    </section>
</template>
