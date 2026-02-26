<script setup lang="ts">
import type {
    AppPageProps,
    Article,
    FilesystemCuratorReview,
    Movie,
    MovieReviewsData,
} from '@/types';

import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, Check, Play, Plus } from 'lucide-vue-next';
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';

import JsonLdScript from '@/components/JsonLdScript.vue';
import MovieCard from '@/components/MovieCard.vue';
import { Poster, StarRating } from '@/components/primitives';
import CoordinateGrid from '@/components/primitives/CoordinateGrid.vue';
import MovieFacts from '@/components/public/MovieFacts.vue';
import MovieGrid from '@/components/public/MovieGrid.vue';
import PublicContainer from '@/components/public/PublicContainer.vue';
import PublicSection from '@/components/public/PublicSection.vue';
import SectionHeader from '@/components/public/SectionHeader.vue';
import CuratorReviewComponent from '@/components/reviews/CuratorReview.vue';
import { Button } from '@/components/ui/button';
import UpcomingBadge from '@/components/UpcomingBadge.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { login, register } from '@/routes';
import {
    extractPosterPath,
    getLocalPosterSrcset,
    getProfileImageUrl,
    getTmdbPosterSizes,
    getTmdbPosterSrcset,
    isTmdbImageUrl,
} from '@/utils/image';

interface Props {
    movie: Movie;
    relatedMovies: Movie[];
    reviews?: MovieReviewsData;
    curatorReview?: FilesystemCuratorReview | null;
    relatedArticles?: Article[];
}

interface PageProps extends AppPageProps {
    movie: Movie;
    relatedMovies: Movie[];
    reviews?: MovieReviewsData;
    curatorReview?: FilesystemCuratorReview | null;
    relatedArticles?: Article[];
}

const props = defineProps<Props>();
const page = usePage<PageProps>();

const auth = page.props.auth;

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

const hasFilesystemCuratorReview = computed(() => Boolean(props.curatorReview));

const canWriteReview = computed(
    () => auth.user && !reviewsData.value.user_review,
);

const hasUserRatings = computed(
    () => (reviewsData.value.summary.user_rating_count ?? 0) > 0,
);

const hasNoReviews = computed(
    () => (reviewsData.value.summary.user_rating_count ?? 0) === 0,
);

const totalReviewCount = computed(
    () => reviewsData.value.summary.user_rating_count ?? 0,
);
const showOneLineReviewSummary = computed(
    () =>
        totalReviewCount.value === 1 &&
        Boolean(reviewsData.value.curator_review),
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

const appUrl = computed(() => (page.props.appUrl as string) ?? '');
const siteUrl = computed(() => appUrl.value || '');

const posterImage = computed(
    () =>
        props.movie.poster_url || '/images/placeholders/poster-placeholder.png',
);

const pageUrl = computed(() =>
    siteUrl.value ? `${siteUrl.value}/movies/${props.movie.slug}` : '',
);

const ogImage = computed(() =>
    props.movie.poster_url?.startsWith('http')
        ? props.movie.poster_url
        : `${siteUrl.value}${props.movie.poster_url || '/images/placeholders/poster-placeholder.png'}`,
);

const ogDescription = computed(() => {
    const synopsis = props.movie.synopsis || '';
    return synopsis.length > 200 ? synopsis.slice(0, 200) + '...' : synopsis;
});

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

const jsonLdMovie = computed(() => {
    const m = props.movie;
    const movieUrl = pageUrl.value;
    const imageUrl = ogImage.value;
    const base: Record<string, unknown> = {
        '@context': 'https://schema.org',
        '@type': 'Movie',
        name: m.title,
        image: imageUrl,
        datePublished: m.release_year?.toString() ?? undefined,
        description: ogDescription.value,
        url: movieUrl,
    };
    const count = reviewsData.value.summary.user_rating_count ?? 0;
    const avg = reviewsData.value.summary.user_rating_average;
    if (count > 1 && avg != null) {
        base.aggregateRating = {
            '@type': 'AggregateRating',
            ratingValue: avg,
            reviewCount: count,
            bestRating: 4,
        };
    }
    const curator = reviewsData.value.curator_review;
    if (curator) {
        base.review = {
            '@type': 'Review',
            author: {
                '@type': 'Person',
                name: curator.user?.name ?? 'Curator',
            },
            reviewRating: {
                '@type': 'Rating',
                ratingValue: curator.rating,
                bestRating: 4,
            },
            reviewBody: curator.content_excerpt || undefined,
        };
    }
    return JSON.stringify(base);
});

const jsonLdBreadcrumb = computed(() =>
    JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        itemListElement: [
            {
                '@type': 'ListItem',
                position: 1,
                name: 'Home',
                item: `${appUrl.value}/`,
            },
            {
                '@type': 'ListItem',
                position: 2,
                name: 'Movies',
                item: `${appUrl.value}/movies`,
            },
            {
                '@type': 'ListItem',
                position: 3,
                name: props.movie.title,
                item: pageUrl.value,
            },
        ],
    }),
);
</script>

<template>
    <PublicLayout>
        <Head :title="`${movie.title} (${movie.release_year})`">
            <meta
                head-key="description"
                name="description"
                :content="ogDescription"
            />
            <link head-key="canonical" rel="canonical" :href="pageUrl" />
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

        <JsonLdScript :content="jsonLdMovie" />
        <JsonLdScript :content="jsonLdBreadcrumb" />

        <div class="relative overflow-hidden bg-[--intel-bg-base]">
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
                    class="absolute inset-0 bg-gradient-to-b from-[--intel-bg-base] via-[--intel-bg-base]/80 to-[--intel-bg-base]"
                />
                <div class="absolute inset-0 bg-[--intel-bg-base]/20" />
                <!-- Coordinate grid pattern -->
                <CoordinateGrid density="sparse" :opacity="0.05" />
            </div>

            <PublicContainer class="relative py-12 sm:py-14 lg:py-16">
                <!-- Back link with hover animation -->
                <div class="mb-6">
                    <Link
                        href="/movies"
                        class="group inline-flex items-center gap-2 font-[family-name:var(--font-mono-display)] text-sm text-[--intel-text-muted] transition-colors hover:text-[--intel-text-primary] focus:outline-none focus-visible:text-[--intel-text-primary]"
                    >
                        <ArrowLeft
                            class="size-4 transition-transform duration-200 group-hover:-translate-x-1"
                        />
                        &lt; BACK TO INDEX
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
                                ? 'translate-x-0 opacity-100'
                                : '-translate-x-3 opacity-0'
                        "
                    >
                        <div
                            class="overflow-hidden rounded-md border border-[--intel-border] transition-all duration-300 hover:shadow-[0_0_20px_var(--intel-accent-glow)]"
                        >
                            <Poster
                                :src="posterImage"
                                :alt="movie.title"
                                :poster-path="movie.poster_path"
                                context="detail"
                                aspect-ratio="2/3"
                                :priority="true"
                                class="shadow-2xl ring-1 ring-[--intel-border] transition-all duration-300 hover:scale-[1.02] hover:ring-[--intel-border-bright]"
                            />
                        </div>

                        <!-- Watchlist button with entrance animation -->
                        <div
                            class="mt-4 transform transition-all duration-700 [transition-timing-function:var(--ease-smooth-out)]"
                            :class="
                                isVisible
                                    ? 'translate-x-0 opacity-100'
                                    : '-translate-x-3 opacity-0'
                            "
                            :style="{ transitionDelay: '500ms' }"
                        >
                            <Button
                                v-if="auth.user"
                                class="w-full border border-[--intel-border] text-[--intel-text-primary] transition-all duration-200 hover:scale-[1.02] hover:border-[--intel-accent]"
                                :class="
                                    movie.is_watchlisted
                                        ? 'hover:shadow-[--intel-accent]/10 hover:shadow-lg'
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
                                class="w-full border border-[--intel-border] text-[--intel-text-primary] transition-all duration-200 hover:scale-[1.02] hover:border-[--intel-accent]"
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
                                class="mb-3 inline-flex transform transition-all duration-700 [transition-timing-function:var(--ease-smooth-out)]"
                                :class="
                                    isVisible
                                        ? 'translate-x-0 opacity-100'
                                        : '-translate-x-3 opacity-0'
                                "
                                :style="{ transitionDelay: '100ms' }"
                            >
                                <UpcomingBadge />
                            </div>

                            <!-- Title with entrance animation -->
                            <h1
                                class="mb-5 transform font-[family-name:var(--font-mono-display)] font-semibold tracking-tight text-balance text-[--intel-text-primary] transition-all duration-700 [transition-timing-function:var(--ease-smooth-out)]"
                                :class="
                                    isVisible
                                        ? 'translate-x-0 opacity-100'
                                        : '-translate-x-3 opacity-0'
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
                                        ? 'translate-x-0 opacity-100'
                                        : '-translate-x-3 opacity-0'
                                "
                                :style="{ transitionDelay: '200ms' }"
                                :movie="movie"
                            />

                            <!-- Your rating and review (when user has reviewed) -->
                            <div
                                v-if="reviewsData.user_review"
                                class="mb-6 flex flex-wrap items-center gap-3 text-sm text-[--intel-text-body]"
                            >
                                <span
                                    class="font-medium text-[--intel-text-muted]"
                                    >Your rating:</span
                                >
                                <StarRating
                                    :rating="reviewsData.user_review.rating"
                                    :max-stars="4"
                                    size="md"
                                />
                                <Link
                                    href="#reviews"
                                    class="font-medium text-[--intel-accent] transition-colors hover:text-[--intel-accent-hover]"
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
                                        ? 'translate-x-0 opacity-100'
                                        : '-translate-x-3 opacity-0'
                                "
                                :style="{ transitionDelay: '300ms' }"
                            >
                                <Link
                                    v-for="tag in movie.tags"
                                    :key="tag.id"
                                    :href="`/movies?tag=${tag.slug}`"
                                    class="rounded-full border border-[--intel-border] bg-[--intel-bg-elevated] px-4 py-1.5 font-[family-name:var(--font-mono-display)] text-xs text-[--intel-text-body] transition-all duration-200 hover:-translate-y-0.5 hover:border-[--intel-border-bright] hover:text-[--intel-text-primary] focus:outline-none focus-visible:ring-2 focus-visible:ring-[--intel-accent]"
                                >
                                    {{ tag.name }}
                                </Link>
                            </div>

                            <!-- Synopsis with entrance animation -->
                            <div
                                class="mb-8 transform transition-all duration-700 [transition-timing-function:var(--ease-smooth-out)]"
                                :class="
                                    isVisible
                                        ? 'translate-x-0 opacity-100'
                                        : '-translate-x-3 opacity-0'
                                "
                                :style="{ transitionDelay: '400ms' }"
                            >
                                <h2
                                    class="text-xl font-semibold text-[--intel-text-primary]"
                                >
                                    Synopsis
                                </h2>
                                <p
                                    class="leading-relaxed text-[--intel-text-body]"
                                >
                                    {{ movie.synopsis }}
                                </p>
                            </div>

                            <!-- Cast section -->
                            <div
                                v-if="movie.cast && movie.cast.length > 0"
                                class="mb-8 transform transition-all duration-700 [transition-timing-function:var(--ease-smooth-out)]"
                                :class="
                                    isVisible
                                        ? 'translate-x-0 opacity-100'
                                        : '-translate-x-3 opacity-0'
                                "
                                :style="{ transitionDelay: '450ms' }"
                            >
                                <h2
                                    class="mb-4 text-xl font-semibold text-[--intel-text-primary]"
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
                                        class="group flex flex-col items-center gap-2 rounded-xl p-3 ring-1 ring-[--intel-border] transition-all hover:bg-[--intel-bg-surface] hover:ring-[--intel-border-bright]"
                                    >
                                        <img
                                            :src="
                                                getProfileImageUrl(
                                                    member.profile_path,
                                                )
                                            "
                                            :alt="member.name"
                                            class="size-16 shrink-0 overflow-hidden rounded-full object-cover ring-1 ring-[--intel-border]"
                                        />
                                        <div class="min-w-0 text-center">
                                            <span
                                                class="block truncate font-[family-name:var(--font-mono-display)] text-xs font-medium text-[--intel-text-primary] group-hover:text-[--intel-accent-hover]"
                                            >
                                                {{ member.name }}
                                            </span>
                                            <span
                                                v-if="member.character"
                                                class="block truncate text-xs text-[--intel-text-muted]"
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
                                        ? 'translate-x-0 opacity-100'
                                        : '-translate-x-3 opacity-0'
                                "
                                :style="{ transitionDelay: '500ms' }"
                            >
                                <h2
                                    class="mb-4 text-xl font-semibold text-[--intel-text-primary]"
                                >
                                    Crew
                                </h2>
                                <dl class="grid gap-2 sm:grid-cols-2">
                                    <div
                                        v-for="(member, idx) in keyCrew"
                                        :key="`${member.tmdb_id}-${member.job}-${idx}`"
                                        class="flex justify-between gap-4 rounded-lg bg-[--intel-bg-surface] px-3 py-2"
                                    >
                                        <dt
                                            class="font-[family-name:var(--font-mono-display)] text-xs text-[--intel-text-muted] uppercase"
                                        >
                                            {{ member.job }}
                                        </dt>
                                        <dd
                                            class="text-sm font-medium text-[--intel-text-body]"
                                        >
                                            <Link
                                                v-if="member.slug"
                                                :href="`/people/${member.slug}`"
                                                class="hover:text-[--intel-accent-hover]"
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
                                        ? 'translate-x-0 opacity-100'
                                        : '-translate-x-3 opacity-0'
                                "
                                :style="{ transitionDelay: '500ms' }"
                            >
                                <!-- Trailer button with enhanced hover -->
                                <a
                                    v-if="movie.trailer_url"
                                    :href="movie.trailer_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-2 rounded-xl bg-[--intel-accent] px-5 py-3 text-sm font-semibold tracking-wide text-white uppercase shadow-[--intel-accent]/20 shadow-lg transition-all duration-200 hover:scale-105 hover:bg-[--intel-accent-dim] hover:shadow-[--intel-accent]/30 hover:shadow-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-[--intel-accent]"
                                >
                                    <Play class="size-5" />
                                    WATCH TRAILER
                                </a>

                                <!-- IMDb link with underline animation -->
                                <a
                                    v-if="movie.imdb_id"
                                    :href="`https://www.imdb.com/title/${movie.imdb_id}/`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="group relative inline-flex items-center rounded-lg border border-[--intel-border] px-4 py-2.5 text-sm text-[--intel-text-primary] transition-colors hover:border-[--intel-accent]"
                                >
                                    <span>View on IMDb</span>
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
                                ? 'translate-x-0 opacity-100'
                                : '-translate-x-3 opacity-0'
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
                                    ? 'translate-x-0 opacity-100'
                                    : '-translate-x-3 opacity-0'
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

                <!-- Filesystem curator review (editorial) -->
                <div
                    v-if="hasFilesystemCuratorReview && curatorReview"
                    class="rounded-lg border border-[--intel-border] bg-[--intel-bg-surface] p-6"
                    role="region"
                    aria-label="Editorial review"
                >
                    <CuratorReviewComponent :review="curatorReview" />
                </div>

                <!-- One-line summary when only curator review -->
                <div
                    v-if="showOneLineReviewSummary && movie.slug"
                    class="flex flex-wrap items-center gap-2 text-sm text-[--intel-text-muted]"
                >
                    <span>1 review</span>
                    <Link
                        :href="`/movies/${movie.slug}/reviews`"
                        class="font-medium text-[--intel-accent] hover:underline"
                    >
                        Read full review
                    </Link>
                </div>

                <!-- Full rating summary when multiple reviews -->
                <div
                    v-else-if="totalReviewCount > 1"
                    class="flex flex-wrap items-center gap-6 rounded-lg border border-[--intel-border] bg-[--intel-bg-surface] p-4"
                >
                    <div v-if="hasUserRatings" class="flex items-center gap-2">
                        <StarRating
                            :rating="
                                reviewsData.summary.user_rating_average ?? 0
                            "
                            :max-stars="4"
                            size="md"
                        />
                        <span class="text-sm text-[--intel-text-muted]">
                            {{ totalReviewCount }} reviews
                        </span>
                    </div>
                    <div
                        v-if="movie.tmdb_vote_average != null"
                        class="flex items-center gap-2 text-sm text-[--intel-text-muted]"
                    >
                        <span
                            >TMDB:
                            {{
                                Number(movie.tmdb_vote_average).toFixed(1)
                            }}/10</span
                        >
                        <span v-if="movie.tmdb_vote_count != null">
                            ({{
                                Number(movie.tmdb_vote_count).toLocaleString()
                            }}
                            votes)
                        </span>
                    </div>
                    <Link
                        v-if="movie.slug"
                        :href="`/movies/${movie.slug}/reviews`"
                        class="text-sm font-medium text-[--intel-accent] hover:underline"
                    >
                        View all reviews
                    </Link>
                </div>

                <!-- Guest empty state: no reviews -->
                <div
                    v-if="hasNoReviews && !auth.user"
                    class="rounded-lg border border-[--intel-border] bg-[--intel-bg-surface] p-6 text-center"
                >
                    <p class="mb-4 text-[--intel-text-body]">
                        Be the first to review
                    </p>
                    <div
                        class="flex flex-wrap items-center justify-center gap-3"
                    >
                        <Link
                            :href="loginUrl"
                            class="inline-flex items-center justify-center rounded-lg bg-[--intel-accent] px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-[--intel-accent-dim] focus:outline-none focus-visible:ring-2 focus-visible:ring-[--intel-accent]"
                        >
                            Log in to review
                        </Link>
                        <span class="text-sm text-[--intel-text-faint]"
                            >or</span
                        >
                        <Link
                            :href="register().url"
                            class="text-sm font-medium text-[--intel-accent] hover:underline"
                        >
                            Sign up
                        </Link>
                    </div>
                </div>

                <!-- Curator review teaser (excerpt + link to full review) -->
                <div
                    v-if="hasCuratorReview && reviewsData.curator_review"
                    class="space-y-3 rounded-lg border border-[--intel-border] bg-[--intel-bg-surface] p-6"
                    role="region"
                    aria-label="Curator review"
                >
                    <div class="flex flex-wrap items-center gap-2">
                        <StarRating
                            :rating="reviewsData.curator_review.rating"
                            :max-stars="4"
                            size="md"
                        />
                        <span class="text-sm text-[--intel-text-muted]">
                            {{
                                reviewsData.curator_review.user?.name ??
                                'Curator'
                            }}'s review
                        </span>
                    </div>
                    <p
                        v-if="reviewsData.curator_review.content_excerpt"
                        class="line-clamp-3 text-[--intel-text-body]"
                    >
                        {{ reviewsData.curator_review.content_excerpt }}
                    </p>
                    <div class="flex flex-wrap items-center gap-4">
                        <Link
                            v-if="movie.slug"
                            :href="`/movies/${movie.slug}/reviews`"
                            class="inline-flex items-center gap-1.5 text-sm font-semibold text-[--intel-accent] transition-colors hover:text-[--intel-accent-hover]"
                        >
                            Read full review
                            <ArrowRight class="size-4" />
                        </Link>
                        <Link
                            v-if="movie.slug && totalReviewCount > 1"
                            :href="`/movies/${movie.slug}/reviews`"
                            class="text-sm font-medium text-[--intel-accent] hover:underline"
                        >
                            View all reviews
                        </Link>
                    </div>
                </div>

                <!-- Write a review (link to reviews page) -->
                <div v-if="canWriteReview">
                    <Link
                        :href="`/movies/${movie.slug}/reviews`"
                        class="inline-flex items-center gap-2 rounded-lg border border-[--intel-border] px-4 py-2 text-sm font-medium text-[--intel-text-body] transition-colors hover:border-[--intel-accent] hover:bg-[--intel-bg-elevated] hover:text-[--intel-text-primary]"
                    >
                        Write a review
                    </Link>
                </div>
            </PublicContainer>
        </PublicSection>

        <!-- Related Articles -->
        <PublicSection
            v-if="relatedArticles && relatedArticles.length > 0"
            spacing="md"
        >
            <PublicContainer class="flex flex-col gap-6">
                <SectionHeader title="Related Articles" :level="2" />
                <div class="space-y-4">
                    <Link
                        v-for="article in relatedArticles"
                        :key="article.id"
                        :href="`/articles/${article.slug}`"
                        class="block rounded-md border border-[--intel-border] bg-[--intel-bg-surface] p-4 transition-colors hover:border-[--intel-border-bright]"
                    >
                        <h3 class="font-medium text-[--intel-text-primary]">
                            {{ article.title }}
                        </h3>
                        <p
                            v-if="article.excerpt"
                            class="mt-1 line-clamp-2 text-sm text-[--intel-text-muted]"
                        >
                            {{ article.excerpt }}
                        </p>
                        <div
                            class="mt-2 flex items-center gap-2 font-[family-name:var(--font-mono-display)] text-xs text-[--intel-text-faint]"
                        >
                            <span v-if="article.published_at">
                                {{
                                    new Date(
                                        article.published_at,
                                    ).toLocaleDateString()
                                }}
                            </span>
                            <span v-if="article.news_source">
                                {{ article.news_source.name }}
                            </span>
                        </div>
                    </Link>
                </div>
            </PublicContainer>
        </PublicSection>
    </PublicLayout>
</template>
