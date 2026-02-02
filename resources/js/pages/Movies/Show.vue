<script setup lang="ts">
import type { AppPageProps, Movie, MovieReviewsData } from '@/types';

import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Check, Play, Plus } from 'lucide-vue-next';
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';

import MovieCard from '@/components/MovieCard.vue';
import { Poster, StarRating } from '@/components/primitives';
import MovieFacts from '@/components/public/MovieFacts.vue';
import MovieGrid from '@/components/public/MovieGrid.vue';
import PublicContainer from '@/components/public/PublicContainer.vue';
import PublicSection from '@/components/public/PublicSection.vue';
import SectionHeader from '@/components/public/SectionHeader.vue';
import ReviewCard from '@/components/reviews/ReviewCard.vue';
import ReviewForm from '@/components/reviews/ReviewForm.vue';
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { login, register } from '@/routes';
import {
    extractPosterPath,
    getProfileImageUrl,
    getTmdbPosterSizes,
    getTmdbPosterSrcset,
    isTmdbImageUrl,
} from '@/utils/image';

interface Props {
    movie: Movie;
    relatedMovies: Movie[];
    reviews?: MovieReviewsData;
}

interface PageProps extends AppPageProps {
    movie: Movie;
    relatedMovies: Movie[];
    reviews?: MovieReviewsData;
}

const props = defineProps<Props>();
const page = usePage<PageProps>();

const auth = page.props.auth;

// Show/hide write review form
const showReviewForm = ref(false);
// Show/hide edit review form (when user has already reviewed)
const showEditReviewForm = ref(false);

// Entrance animation state
const isVisible = ref(false);
const relatedVisible = ref(false);
const relatedSection = ref<HTMLElement | null>(null);

const reviewsData = computed(
    () =>
        props.reviews ?? {
            summary: { user_rating_average: null, user_rating_count: 0 },
            user_review: null,
            recent: [],
            curator_review: null,
        },
);

const hasCuratorReview = computed(() =>
    Boolean(reviewsData.value.curator_review),
);

const canWriteReview = computed(
    () => auth.user && !reviewsData.value.user_review,
);

const hasUserRatings = computed(
    () => (reviewsData.value.summary.user_rating_count ?? 0) > 0,
);

const hasNoReviews = computed(
    () => (reviewsData.value.summary.user_rating_count ?? 0) === 0,
);

const loginUrl = computed(
    () => login({ query: { redirect: `/movies/${props.movie.slug}` } }).url,
);

let observer: IntersectionObserver | null = null;

onMounted(() => {
    // Trigger entrance animations
    requestAnimationFrame(() => {
        isVisible.value = true;
    });

    // Set up Intersection Observer for related films section
    // Wait for nextTick to ensure component is fully mounted
    void nextTick(() => {
        const element = relatedSection.value;
        if (element && element instanceof HTMLElement) {
            observer = new IntersectionObserver(
                (entries) => {
                    const entry = entries[0];
                    if (entry?.isIntersecting) {
                        relatedVisible.value = true;
                        observer?.disconnect();
                    }
                },
                { threshold: 0.1 },
            );
            observer.observe(element);
        }
    });
});

onUnmounted(() => {
    observer?.disconnect();
});

const toggleWatchlist = () => {
    if (!auth.user) {
        router.visit('/login');
        return;
    }

    if (props.movie.is_watchlisted) {
        router.delete(`/watchlist/${props.movie.id}`, {
            preserveScroll: true,
        });
    } else {
        router.post(
            `/watchlist/${props.movie.id}`,
            {},
            {
                preserveScroll: true,
            },
        );
    }
};

const siteUrl = 'https://movies-of-war.com';

const posterImage = computed(
    () =>
        props.movie.poster_url || '/images/placeholders/poster-placeholder.png',
);

const pageUrl = computed(() => `${siteUrl}/movies/${props.movie.slug}`);

const ogImage = computed(() =>
    props.movie.poster_url?.startsWith('http')
        ? props.movie.poster_url
        : `${siteUrl}${props.movie.poster_url || '/images/placeholders/poster-placeholder.png'}`,
);

const ogDescription = computed(() => {
    const synopsis = props.movie.synopsis || '';
    return synopsis.length > 200 ? synopsis.slice(0, 200) + '...' : synopsis;
});

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

const keyCrewJobs = [
    'Director',
    'Writer',
    'Screenplay',
    'Director of Photography',
    'Cinematography',
    'Editing',
    'Editor',
    'Music',
    'Original Music Composer',
];

const keyCrew = computed(() => {
    const crew = props.movie.crew ?? [];
    const seen = new Set<string>();
    return crew
        .filter((c: { name: string; job: string }) => {
            const key = `${c.name}|${c.job}`;
            if (seen.has(key)) return false;
            if (
                !keyCrewJobs.some((job) =>
                    c.job.toLowerCase().includes(job.toLowerCase()),
                )
            )
                return false;
            seen.add(key);
            return true;
        })
        .slice(0, 10);
});
</script>

<template>
    <PublicLayout>
        <Head :title="`${movie.title} (${movie.release_year}) - Movies of War`">
            <meta name="description" :content="ogDescription" />
            <meta property="og:type" content="video.movie" />
            <meta
                property="og:title"
                :content="`${movie.title} (${movie.release_year})`"
            />
            <meta property="og:description" :content="ogDescription" />
            <meta property="og:image" :content="ogImage" />
            <meta property="og:url" :content="pageUrl" />
            <meta property="og:site_name" content="Movies of War" />
            <meta name="twitter:card" content="summary_large_image" />
            <meta
                name="twitter:title"
                :content="`${movie.title} (${movie.release_year})`"
            />
            <meta name="twitter:description" :content="ogDescription" />
            <meta name="twitter:image" :content="ogImage" />
        </Head>

        <div class="relative overflow-hidden bg-zinc-950">
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
                        class="h-full w-full scale-110 object-cover opacity-25 blur-2xl transition-transform duration-[2000ms] [transition-timing-function:var(--ease-smooth-out)]"
                        :class="isVisible ? 'scale-100' : 'scale-110'"
                        fetchpriority="high"
                        decoding="async"
                    />
                </picture>
                <img
                    v-else
                    :src="posterImage"
                    :alt="movie.title"
                    class="h-full w-full scale-110 object-cover opacity-25 blur-2xl transition-transform duration-[2000ms] [transition-timing-function:var(--ease-smooth-out)]"
                    :class="isVisible ? 'scale-100' : 'scale-110'"
                    fetchpriority="high"
                    decoding="async"
                />
                <!-- Multiple gradient layers for depth -->
                <div
                    class="absolute inset-0 bg-gradient-to-b from-zinc-950 via-zinc-950/80 to-zinc-950"
                />
                <div class="absolute inset-0 bg-zinc-950/20" />
                <!-- Film texture pattern -->
                <div
                    class="pointer-events-none absolute inset-0 [background-image:radial-gradient(circle_at_1px_1px,white_1px,transparent_0)] [background-size:18px_18px] opacity-[0.07]"
                />
            </div>

            <PublicContainer class="relative py-12 sm:py-14 lg:py-16">
                <!-- Back link with hover animation -->
                <div class="mb-6">
                    <Link
                        href="/movies"
                        class="group inline-flex items-center gap-2 text-sm text-zinc-300 transition-colors hover:text-white focus:outline-none focus-visible:text-white"
                    >
                        <ArrowLeft
                            class="size-4 transition-transform duration-200 group-hover:-translate-x-1"
                        />
                        Back to Movies
                    </Link>
                </div>

                <div
                    class="movie-detail-grid grid grid-cols-1 gap-8"
                    style="container-type: inline-size"
                >
                    <!-- Poster with entrance animation and hover effects -->
                    <div
                        class="movie-detail-poster w-full transform transition-all duration-700 [transition-timing-function:var(--ease-smooth-out)]"
                        :class="
                            isVisible
                                ? 'translate-y-0 opacity-100'
                                : 'translate-y-8 opacity-0'
                        "
                    >
                        <div
                            class="overflow-hidden rounded-2xl transition-all duration-300 hover:shadow-[0_25px_50px_-12px_rgba(0,0,0,0.5)]"
                        >
                            <Poster
                                :src="posterImage"
                                :alt="movie.title"
                                :poster-path="movie.poster_path"
                                context="detail"
                                aspect-ratio="2/3"
                                :priority="true"
                                class="shadow-2xl ring-1 ring-white/10 transition-all duration-300 hover:scale-[1.02] hover:ring-white/20"
                            />
                        </div>

                        <!-- Watchlist button with entrance animation -->
                        <div
                            class="mt-4 transform transition-all duration-700 [transition-timing-function:var(--ease-smooth-out)]"
                            :class="
                                isVisible
                                    ? 'translate-y-0 opacity-100'
                                    : 'translate-y-4 opacity-0'
                            "
                            :style="{ transitionDelay: '500ms' }"
                        >
                            <Button
                                v-if="auth.user"
                                class="w-full transition-all duration-200 hover:scale-[1.02]"
                                :class="
                                    movie.is_watchlisted
                                        ? 'hover:shadow-lg hover:shadow-red-600/10'
                                        : ''
                                "
                                :variant="
                                    movie.is_watchlisted ? 'outline' : 'default'
                                "
                                @click="toggleWatchlist"
                            >
                                <component
                                    :is="movie.is_watchlisted ? Check : Plus"
                                    class="size-4"
                                />
                                {{
                                    movie.is_watchlisted
                                        ? 'In Watchlist'
                                        : 'Add to Watchlist'
                                }}
                            </Button>
                            <Button
                                v-else
                                class="w-full transition-all duration-200 hover:scale-[1.02]"
                                variant="default"
                                @click="toggleWatchlist"
                            >
                                <Plus class="size-4" />
                                Add to Watchlist
                            </Button>
                        </div>
                    </div>

                    <!-- Content section with staggered entrance animations -->
                    <div class="movie-detail-content flex flex-col">
                        <div>
                            <!-- Coming Soon badge -->
                            <div
                                v-if="movie.is_upcoming"
                                class="mb-3 inline-flex transform rounded-full bg-red-600/90 px-3 py-1 text-xs font-semibold text-white transition-all duration-700 [transition-timing-function:var(--ease-smooth-out)]"
                                :class="
                                    isVisible
                                        ? 'translate-y-0 opacity-100'
                                        : 'translate-y-4 opacity-0'
                                "
                                :style="{ transitionDelay: '100ms' }"
                            >
                                Coming Soon
                            </div>

                            <!-- Title with entrance animation -->
                            <h1
                                class="mb-5 transform font-semibold tracking-tight text-balance text-white transition-all duration-700 [transition-timing-function:var(--ease-smooth-out)]"
                                :class="
                                    isVisible
                                        ? 'translate-y-0 opacity-100'
                                        : 'translate-y-4 opacity-0'
                                "
                                :style="{ transitionDelay: '100ms' }"
                                style="
                                    font-size: clamp(2rem, 3.5vw + 1rem, 3rem);
                                "
                            >
                                {{ movie.title }}
                            </h1>

                            <!-- MovieFacts with entrance animation -->
                            <MovieFacts
                                class="mb-8 transform transition-all duration-700 [transition-timing-function:var(--ease-smooth-out)]"
                                :class="
                                    isVisible
                                        ? 'translate-y-0 opacity-100'
                                        : 'translate-y-4 opacity-0'
                                "
                                :style="{ transitionDelay: '200ms' }"
                                :movie="movie"
                            />

                            <!-- Your rating and review (when user has reviewed) -->
                            <div
                                v-if="reviewsData.user_review"
                                class="mb-6 flex flex-wrap items-center gap-3 text-sm text-zinc-300"
                            >
                                <span class="font-medium text-zinc-400"
                                    >Your rating:</span
                                >
                                <StarRating
                                    :rating="reviewsData.user_review.rating"
                                    :max-stars="4"
                                    size="md"
                                />
                                <Link
                                    href="#reviews"
                                    class="font-medium text-red-500 transition-colors hover:text-red-400"
                                >
                                    Your review
                                </Link>
                            </div>

                            <!-- Tags with entrance animation and hover effects -->
                            <div
                                v-if="movie.tags && movie.tags.length > 0"
                                class="mb-6 flex transform flex-wrap gap-2 transition-all duration-700 [transition-timing-function:var(--ease-smooth-out)]"
                                :class="
                                    isVisible
                                        ? 'translate-y-0 opacity-100'
                                        : 'translate-y-4 opacity-0'
                                "
                                :style="{ transitionDelay: '300ms' }"
                            >
                                <Link
                                    v-for="tag in movie.tags"
                                    :key="tag.id"
                                    :href="`/movies?tag=${tag.slug}`"
                                    class="rounded-full bg-zinc-950 px-4 py-1.5 text-sm text-zinc-200 ring-1 ring-zinc-800/70 transition-all duration-200 hover:-translate-y-0.5 hover:bg-zinc-800 hover:text-white hover:ring-zinc-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                                >
                                    {{ tag.name }}
                                </Link>
                            </div>

                            <!-- Synopsis with entrance animation -->
                            <div
                                class="mb-8 transform transition-all duration-700 [transition-timing-function:var(--ease-smooth-out)]"
                                :class="
                                    isVisible
                                        ? 'translate-y-0 opacity-100'
                                        : 'translate-y-4 opacity-0'
                                "
                                :style="{ transitionDelay: '400ms' }"
                            >
                                <h2 class="text-xl font-semibold text-white">
                                    Synopsis
                                </h2>
                                <p class="leading-relaxed text-zinc-300">
                                    {{ movie.synopsis }}
                                </p>
                            </div>

                            <!-- Cast section -->
                            <div
                                v-if="movie.cast && movie.cast.length > 0"
                                class="mb-8 transform transition-all duration-700 [transition-timing-function:var(--ease-smooth-out)]"
                                :class="
                                    isVisible
                                        ? 'translate-y-0 opacity-100'
                                        : 'translate-y-4 opacity-0'
                                "
                                :style="{ transitionDelay: '450ms' }"
                            >
                                <h2
                                    class="mb-4 text-xl font-semibold text-white"
                                >
                                    Cast
                                </h2>
                                <div
                                    class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4"
                                >
                                    <Link
                                        v-for="(member, idx) in movie.cast"
                                        :key="`${member.tmdb_id}-${idx}`"
                                        :href="
                                            member.slug
                                                ? `/people/${member.slug}`
                                                : '#'
                                        "
                                        class="group flex flex-col items-center gap-2 rounded-xl p-3 ring-1 ring-zinc-800/70 transition-all hover:bg-zinc-900/60 hover:ring-zinc-700"
                                    >
                                        <img
                                            :src="
                                                getProfileImageUrl(
                                                    member.profile_path,
                                                )
                                            "
                                            :alt="member.name"
                                            class="size-16 shrink-0 overflow-hidden rounded-full object-cover ring-1 ring-zinc-700"
                                        />
                                        <div class="min-w-0 text-center">
                                            <span
                                                class="block truncate text-sm font-medium text-white group-hover:text-red-400"
                                            >
                                                {{ member.name }}
                                            </span>
                                            <span
                                                v-if="member.character"
                                                class="block truncate text-xs text-zinc-400"
                                            >
                                                {{ member.character }}
                                            </span>
                                        </div>
                                    </Link>
                                </div>
                            </div>

                            <!-- Crew section -->
                            <div
                                v-if="keyCrew.length > 0"
                                class="mb-8 transform transition-all duration-700 [transition-timing-function:var(--ease-smooth-out)]"
                                :class="
                                    isVisible
                                        ? 'translate-y-0 opacity-100'
                                        : 'translate-y-4 opacity-0'
                                "
                                :style="{ transitionDelay: '500ms' }"
                            >
                                <h2
                                    class="mb-4 text-xl font-semibold text-white"
                                >
                                    Crew
                                </h2>
                                <dl class="grid gap-2 sm:grid-cols-2">
                                    <div
                                        v-for="(member, idx) in keyCrew"
                                        :key="`${member.tmdb_id}-${member.job}-${idx}`"
                                        class="flex justify-between gap-4 rounded-lg bg-zinc-900/40 px-3 py-2"
                                    >
                                        <dt class="text-sm text-zinc-400">
                                            {{ member.job }}
                                        </dt>
                                        <dd
                                            class="text-sm font-medium text-zinc-200"
                                        >
                                            <Link
                                                v-if="member.slug"
                                                :href="`/people/${member.slug}`"
                                                class="hover:text-red-400"
                                            >
                                                {{ member.name }}
                                            </Link>
                                            <span v-else>{{
                                                member.name
                                            }}</span>
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <!-- Action buttons with entrance animation -->
                            <div
                                class="flex transform flex-wrap items-center gap-4 transition-all duration-700 [transition-timing-function:var(--ease-smooth-out)]"
                                :class="
                                    isVisible
                                        ? 'translate-y-0 opacity-100'
                                        : 'translate-y-4 opacity-0'
                                "
                                :style="{ transitionDelay: '500ms' }"
                            >
                                <!-- Trailer button with enhanced hover -->
                                <a
                                    v-if="movie.trailer_url"
                                    :href="movie.trailer_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-red-600/20 transition-all duration-200 hover:scale-105 hover:bg-red-700 hover:shadow-xl hover:shadow-red-600/30 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                                >
                                    <Play class="size-5" />
                                    Watch Trailer
                                </a>

                                <!-- IMDb link with underline animation -->
                                <a
                                    v-if="movie.imdb_id"
                                    :href="`https://www.imdb.com/title/${movie.imdb_id}/`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="group relative text-sm text-zinc-300 transition-colors hover:text-zinc-200"
                                >
                                    <span>View on IMDb</span>
                                    <span
                                        class="absolute bottom-0 left-0 h-px w-0 bg-zinc-300 transition-all duration-300 group-hover:w-full"
                                    />
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </PublicContainer>
        </div>

        <!-- Related Films section with scroll-triggered animation -->
        <PublicSection v-if="relatedMovies.length > 0" spacing="md">
            <div ref="relatedSection">
                <PublicContainer class="flex flex-col gap-6">
                    <SectionHeader
                        title="Related Films"
                        :level="2"
                        class="transform transition-all duration-700 [transition-timing-function:var(--ease-smooth-out)]"
                        :class="
                            relatedVisible
                                ? 'translate-y-0 opacity-100'
                                : 'translate-y-8 opacity-0'
                        "
                    />
                    <MovieGrid>
                        <MovieCard
                            v-for="(movie, index) in relatedMovies"
                            :key="movie.id"
                            :movie="movie"
                            class="transform transition-all duration-700 [transition-timing-function:var(--ease-smooth-out)]"
                            :class="
                                relatedVisible
                                    ? 'translate-y-0 opacity-100'
                                    : 'translate-y-8 opacity-0'
                            "
                            :style="{
                                transitionDelay: `${100 + index * 50}ms`,
                            }"
                        />
                    </MovieGrid>
                </PublicContainer>
            </div>
        </PublicSection>

        <!-- Reviews section -->
        <PublicSection id="reviews" spacing="md">
            <PublicContainer class="flex flex-col gap-6">
                <SectionHeader title="Reviews" :level="2" />

                <!-- Rating summary -->
                <div
                    class="flex flex-wrap items-center gap-6 rounded-lg border border-zinc-800 bg-zinc-900/50 p-4"
                >
                    <div v-if="hasUserRatings" class="flex items-center gap-2">
                        <StarRating
                            :rating="
                                reviewsData.summary.user_rating_average ?? 0
                            "
                            :max-stars="4"
                            size="md"
                        />
                        <span class="text-sm text-zinc-400">
                            {{ reviewsData.summary.user_rating_count }} review{{
                                reviewsData.summary.user_rating_count !== 1
                                    ? 's'
                                    : ''
                            }}
                        </span>
                    </div>
                    <div
                        v-if="movie.tmdb_vote_average != null"
                        class="flex items-center gap-2 text-sm text-zinc-400"
                    >
                        <span
                            >TMDB:
                            {{ movie.tmdb_vote_average.toFixed(1) }}/10</span
                        >
                        <span v-if="movie.tmdb_vote_count != null">
                            ({{ movie.tmdb_vote_count.toLocaleString() }} votes)
                        </span>
                    </div>
                    <Link
                        v-if="movie.slug"
                        :href="`/movies/${movie.slug}/reviews`"
                        class="text-sm font-medium text-red-500 hover:underline"
                    >
                        View all reviews
                    </Link>
                </div>

                <!-- Guest empty state: no reviews, prompt to log in or sign up -->
                <div
                    v-if="hasNoReviews && !auth.user"
                    class="rounded-lg border border-zinc-800 bg-zinc-900/50 p-6 text-center"
                >
                    <p class="mb-4 text-zinc-300">Be the first to review</p>
                    <div
                        class="flex flex-wrap items-center justify-center gap-3"
                    >
                        <Link
                            :href="loginUrl"
                            class="inline-flex items-center justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                        >
                            Log in to review
                        </Link>
                        <span class="text-sm text-zinc-500">or</span>
                        <Link
                            :href="register().url"
                            class="text-sm font-medium text-red-500 hover:underline"
                        >
                            Sign up
                        </Link>
                    </div>
                </div>

                <!-- Write review CTA / form -->
                <div v-if="canWriteReview" class="space-y-4">
                    <Button
                        v-if="!showReviewForm"
                        variant="outline"
                        class="border-zinc-700 text-zinc-300 hover:bg-zinc-800 hover:text-white"
                        @click="showReviewForm = true"
                    >
                        Write a review
                    </Button>
                    <div
                        v-else
                        class="rounded-lg border border-zinc-800 bg-zinc-900/50 p-6"
                    >
                        <h3 class="mb-4 text-lg font-semibold text-white">
                            Write a review
                        </h3>
                        <ReviewForm :movie="movie" :existing-review="null" />
                    </div>
                </div>

                <!-- Curator's review (Russell Jones / site curator) -->
                <div
                    v-if="hasCuratorReview && reviewsData.curator_review"
                    class="space-y-4"
                    role="region"
                    aria-labelledby="curator-review-heading"
                >
                    <h2
                        id="curator-review-heading"
                        class="text-xl font-semibold text-white"
                    >
                        {{
                            reviewsData.curator_review.user?.name ?? 'Curator'
                        }}'s review
                    </h2>
                    <ReviewCard
                        :review="reviewsData.curator_review"
                        :hide-spoiler-blur="true"
                        :is-curator-pick="true"
                        @edit="showEditReviewForm = true"
                    />
                    <div
                        v-if="
                            showEditReviewForm &&
                            reviewsData.user_review?.id ===
                                reviewsData.curator_review?.id
                        "
                        class="rounded-lg border border-zinc-800 bg-zinc-900/50 p-6"
                    >
                        <h3 class="mb-4 text-base font-semibold text-white">
                            Edit your review
                        </h3>
                        <ReviewForm
                            :movie="movie"
                            :existing-review="reviewsData.curator_review"
                        />
                    </div>
                </div>

                <!-- User's own review (when not the curator's review) -->
                <div
                    v-if="
                        reviewsData.user_review &&
                        reviewsData.user_review.id !==
                            reviewsData.curator_review?.id
                    "
                    class="space-y-4"
                >
                    <h3 class="text-lg font-semibold text-white">
                        Your review
                    </h3>
                    <ReviewCard
                        :review="reviewsData.user_review"
                        :hide-spoiler-blur="true"
                        @edit="showEditReviewForm = true"
                    />
                    <div
                        v-if="showEditReviewForm"
                        class="rounded-lg border border-zinc-800 bg-zinc-900/50 p-6"
                    >
                        <h4 class="mb-4 text-base font-semibold text-white">
                            Edit your review
                        </h4>
                        <ReviewForm
                            :movie="movie"
                            :existing-review="reviewsData.user_review"
                        />
                    </div>
                </div>

                <!-- Recent reviews -->
                <div v-if="reviewsData.recent.length > 0" class="space-y-4">
                    <h3 class="text-lg font-semibold text-white">
                        Recent reviews
                    </h3>
                    <div class="flex flex-col gap-4">
                        <ReviewCard
                            v-for="review in reviewsData.recent"
                            :key="review.id"
                            :review="review"
                            :hide-spoiler-blur="false"
                        />
                    </div>
                </div>
            </PublicContainer>
        </PublicSection>
    </PublicLayout>
</template>
