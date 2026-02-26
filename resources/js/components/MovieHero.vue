<script setup lang="ts">
import type { Movie, Review, UserReviewSummary } from '@/types';

import { Link } from '@inertiajs/vue3';
import { ArrowRight, Info, Play } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

import {
    CoordinateGrid,
    GradientOverlay,
    Poster,
    StarRating,
} from '@/components/primitives';
import PublicContainer from '@/components/public/PublicContainer.vue';
import {
    extractPosterPath,
    getLocalPosterSrcset,
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
    /** Current user's review for this movie: shows rating and link to "Your review" */
    userReview?: UserReviewSummary | null;
}

const props = defineProps<Props>();

const posterImage = computed(
    () =>
        props.movie.poster_url || '/images/placeholders/poster-placeholder.png',
);

const localPosterPath = computed(() => {
    const pp = props.movie.poster_path;
    if (pp && (pp.startsWith('posters/') || pp.includes('/storage/posters/'))) {
        const match = pp.match(/posters\/(.+)$/);
        return match ? `posters/${match[1]}` : null;
    }
    return null;
});

const tmdbPosterPath = computed(() => {
    if (localPosterPath.value) return null;

    if (props.movie.poster_path) {
        return props.movie.poster_path;
    }

    if (isTmdbImageUrl(props.movie.poster_url)) {
        return extractPosterPath(props.movie.poster_url);
    }

    return null;
});

const backgroundAvifSrcset = computed(() => {
    if (!localPosterPath.value) return '';
    return getLocalPosterSrcset(localPosterPath.value, 'hero', 'avif');
});

const backgroundWebpSrcset = computed(() => {
    if (localPosterPath.value) {
        return getLocalPosterSrcset(localPosterPath.value, 'hero', 'webp');
    }
    if (tmdbPosterPath.value) {
        return getTmdbPosterSrcset(tmdbPosterPath.value, 'hero', true);
    }
    return '';
});

const backgroundJpegSrcset = computed(() => {
    if (localPosterPath.value) {
        return getLocalPosterSrcset(localPosterPath.value, 'hero', 'original');
    }
    if (tmdbPosterPath.value) {
        return getTmdbPosterSrcset(tmdbPosterPath.value, 'hero', false);
    }
    return '';
});

const backgroundSizes = computed(() => {
    if (!localPosterPath.value && !tmdbPosterPath.value) return undefined;
    return getTmdbPosterSizes('hero');
});

const useResponsiveBackground = computed(() => {
    return Boolean(
        (localPosterPath.value || tmdbPosterPath.value) &&
        (backgroundAvifSrcset.value ||
            backgroundWebpSrcset.value ||
            backgroundJpegSrcset.value),
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
        class="relative overflow-hidden bg-[--intel-bg-base]"
    >
        <!-- Background layers -->
        <div class="absolute inset-0">
            <!-- Blurred poster background with scale animation -->
            <picture v-if="useResponsiveBackground">
                <source
                    v-if="backgroundAvifSrcset"
                    type="image/avif"
                    :srcset="backgroundAvifSrcset"
                    :sizes="backgroundSizes"
                />
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
            <div class="absolute inset-0 bg-[--intel-bg-base]/20" />

            <!-- Coordinate grid pattern -->
            <CoordinateGrid :opacity="0.03" density="normal" />
        </div>

        <PublicContainer as="div" class="relative py-14 sm:py-16 lg:py-20">
            <div class="grid gap-8 lg:grid-cols-12">
                <!-- Poster with entrance animation -->
                <div
                    class="transition-all delay-150 duration-700 [transition-timing-function:var(--ease-smooth-out)] lg:col-span-4"
                    :class="
                        isVisible
                            ? 'translate-x-0 opacity-100'
                            : '-translate-x-3 opacity-0'
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
                            ? 'translate-x-0 opacity-100'
                            : '-translate-x-3 opacity-0'
                    "
                >
                    <div
                        v-if="subtitle"
                        class="mb-2 font-[family-name:var(--font-mono-display)] text-xs font-semibold tracking-[0.2em] text-[--intel-accent] uppercase"
                    >
                        <span
                            class="mr-3 inline-block h-px w-6 bg-[--intel-accent] align-middle"
                        ></span>
                        {{ subtitle }}
                    </div>

                    <h1
                        class="mb-4 font-[family-name:var(--font-mono-display)] font-bold tracking-tight text-balance text-[--intel-text-primary]"
                        style="font-size: clamp(2rem, 4vw + 1rem, 3.75rem)"
                    >
                        {{ movie.title }}
                    </h1>

                    <div
                        class="mb-6 flex flex-wrap items-center gap-4 text-sm text-[--intel-text-body]"
                    >
                        <span class="text-lg font-semibold">{{
                            movie.release_year
                        }}</span>
                        <span
                            v-if="movie.runtime"
                            class="flex items-center gap-1"
                        >
                            <span class="text-[--intel-text-muted]">|</span>
                            {{ movie.runtime }} min
                        </span>
                        <span
                            v-if="movie.conflict"
                            class="rounded-full bg-[--intel-bg-elevated] px-4 py-1.5 text-xs font-semibold ring-1 ring-[--intel-border] backdrop-blur-sm"
                        >
                            {{ movie.conflict }}
                        </span>
                        <StarRating
                            v-if="review"
                            :rating="review.rating"
                            :max-stars="4"
                            size="md"
                        />
                        <span
                            v-if="userReview"
                            class="flex items-center gap-2 text-sm text-[--intel-text-body]"
                        >
                            <span class="text-[--intel-text-muted]"
                                >Your rating:</span
                            >
                            <StarRating
                                :rating="userReview.rating"
                                :max-stars="4"
                                size="md"
                            />
                            <Link
                                :href="`/movies/${movie.slug}/reviews`"
                                class="font-medium text-[--intel-accent] transition-colors hover:text-[--intel-accent-hover]"
                            >
                                Your review
                            </Link>
                        </span>
                    </div>

                    <p
                        class="mb-8 line-clamp-4 text-lg leading-relaxed text-[--intel-text-body]"
                    >
                        {{ movie.synopsis }}
                    </p>

                    <div
                        v-if="review?.content_excerpt"
                        class="mb-8 rounded-lg border border-[--intel-border] bg-[--intel-bg-elevated]/50 p-6"
                    >
                        <p class="mb-4 line-clamp-3 text-[--intel-text-body]">
                            {{ review.content_excerpt }}
                        </p>
                        <Link
                            :href="`/movies/${movie.slug}/reviews`"
                            class="inline-flex items-center gap-1.5 text-sm font-semibold text-[--intel-accent] transition-colors hover:text-[--intel-accent-hover]"
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
                            class="rounded-full border border-[--intel-border] bg-[--intel-bg-elevated] px-4 py-1.5 font-[family-name:var(--font-mono-display)] text-xs text-[--intel-text-body] backdrop-blur-sm transition-colors hover:border-[--intel-accent]"
                        >
                            {{ tag.name }}
                        </span>
                    </div>

                    <div class="flex flex-wrap gap-4">
                        <Link
                            :href="`/movies/${movie.slug}`"
                            class="inline-flex items-center gap-2 rounded-xl bg-[--intel-accent] px-6 py-3.5 text-sm font-semibold text-white shadow-[--intel-accent]/20 shadow-lg transition-all hover:bg-[--intel-accent-dim] hover:shadow-[--intel-accent]/30 focus:outline-none focus-visible:ring-2 focus-visible:ring-[--intel-accent]"
                        >
                            <Info class="size-5" />
                            VIEW DOSSIER
                        </Link>

                        <a
                            v-if="movie.trailer_url"
                            :href="movie.trailer_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 rounded-xl border border-[--intel-border] bg-[--intel-bg-elevated]/80 px-6 py-3.5 text-sm font-semibold text-[--intel-text-primary] backdrop-blur-sm transition-all hover:border-[--intel-accent] focus:outline-none focus-visible:ring-2 focus-visible:ring-[--intel-accent]"
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
