<script setup lang="ts">
import type { AppPageProps, FilesystemCuratorReview, Movie } from '@/types';
import type { PaginationMeta, Review } from '@/types/models';

import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import JsonLdScript from '@/components/JsonLdScript.vue';
import { Poster } from '@/components/primitives';
import MovieFacts from '@/components/public/MovieFacts.vue';
import PublicContainer from '@/components/public/PublicContainer.vue';
import PublicSection from '@/components/public/PublicSection.vue';
import SectionHeader from '@/components/public/SectionHeader.vue';
import CuratorReviewComponent from '@/components/reviews/CuratorReview.vue';
import ReviewCard from '@/components/reviews/ReviewCard.vue';
import ReviewForm from '@/components/reviews/ReviewForm.vue';
import ReviewList from '@/components/reviews/ReviewList.vue';
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { login, register } from '@/routes';

interface Props {
    movie: Movie;
    curator_review?: Review | null;
    curatorReview?: FilesystemCuratorReview | null;
    reviews: {
        data: Review[];
        meta: PaginationMeta;
        links: { url: string | null; label: string; active: boolean }[];
    };
    queryParams?: {
        show_spoilers?: boolean;
        sort?: string;
    };
}

const props = defineProps<Props>();
const page = usePage<AppPageProps & Props>();
const auth = page.props.auth;
const showReviewForm = ref(false);

const appUrl = computed(() => (page.props.appUrl as string) ?? '');
const canonicalUrl = computed(() =>
    appUrl.value && props.movie.slug
        ? `${appUrl.value}/movies/${props.movie.slug}/reviews`
        : '',
);
const reviewsDescription = computed(
    () => `Curator and user reviews for ${props.movie.title}`,
);
const ogImage = computed(() => {
    const url = props.movie.poster_url;
    if (!url)
        return appUrl.value
            ? `${appUrl.value}/images/placeholders/poster-placeholder.png`
            : '';
    return url.startsWith('http') ? url : `${appUrl.value}${url}`;
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
                item: appUrl.value
                    ? `${appUrl.value}/movies/${props.movie.slug}`
                    : '',
            },
            {
                '@type': 'ListItem',
                position: 4,
                name: 'Reviews',
                item: canonicalUrl.value,
            },
        ],
    }),
);

const hasFilesystemCuratorReview = computed(
    () => Boolean(props.curatorReview),
);
const hasDbCuratorReview = computed(() => Boolean(props.curator_review));
const hasCuratorReview = computed(
    () => hasFilesystemCuratorReview.value || hasDbCuratorReview.value,
);
const hasUserReviews = computed(() => (props.reviews?.data?.length ?? 0) > 0);
const hasNoReviews = computed(
    () => !hasCuratorReview.value && (props.reviews?.data?.length ?? 0) === 0,
);
const onlyCuratorNoUserReviews = computed(
    () => hasCuratorReview.value && !hasUserReviews.value,
);
const showWriteReviewSection = computed(
    () =>
        Boolean(auth.user) &&
        !(hasCuratorReview.value && !hasUserReviews.value),
);
const loginUrl = computed(
    () =>
        login({ query: { redirect: `/movies/${props.movie.slug}/reviews` } })
            .url,
);
</script>

<template>
    <PublicLayout>
        <Head :title="`Reviews for ${movie.title}`">
            <meta
                head-key="description"
                name="description"
                :content="reviewsDescription"
            />
            <link head-key="canonical" rel="canonical" :href="canonicalUrl" />
            <meta property="og:type" content="website" />
            <meta property="og:title" :content="`Reviews for ${movie.title}`" />
            <meta property="og:description" :content="reviewsDescription" />
            <meta property="og:image" :content="ogImage" />
            <meta property="og:url" :content="canonicalUrl" />
            <meta property="og:site_name" content="Movies of War" />
            <meta name="twitter:card" content="summary_large_image" />
            <meta
                name="twitter:title"
                :content="`Reviews for ${movie.title}`"
            />
            <meta name="twitter:description" :content="reviewsDescription" />
            <meta name="twitter:image" :content="ogImage" />
        </Head>

        <JsonLdScript :content="jsonLdBreadcrumb" />

        <PublicSection spacing="md">
            <PublicContainer class="flex flex-col gap-6">
                <h1 class="sr-only">Reviews – {{ movie.title }}</h1>

                <Link
                    :href="`/movies/${movie.slug}`"
                    class="inline-flex items-center gap-2 text-sm text-zinc-400 transition-colors hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                >
                    <ArrowLeft class="size-4" aria-hidden="true" />
                    Back to {{ movie.title }}
                </Link>

                <!-- Responsive layout: mobile stacked; desktop grid (main first in DOM, aside second; CSS places aside left, main right) -->
                <div
                    class="flex flex-col gap-6 lg:grid lg:grid-cols-[minmax(0,280px)_1fr] lg:items-start lg:gap-8"
                >
                    <!-- Main column (curator review when present, or empty so "All reviews" follows; first in DOM for reading order) -->
                    <div class="min-w-0 lg:col-start-2 lg:row-start-1">
                        <!-- Mobile: poster first -->
                        <div class="lg:hidden">
                            <Link
                                :href="`/movies/${movie.slug}`"
                                class="block focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                            >
                                <Poster
                                    :src="movie.poster_url"
                                    :alt="movie.title"
                                    :poster-path="movie.poster_path"
                                    context="grid"
                                    class="w-full max-w-[200px] rounded-lg"
                                />
                            </Link>
                            <p class="mt-2 text-lg font-semibold text-white">
                                <Link
                                    :href="`/movies/${movie.slug}`"
                                    class="transition-colors hover:text-red-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                                >
                                    {{ movie.title }}
                                </Link>
                                <template v-if="movie.release_year">
                                    <span class="text-zinc-400">
                                        ({{ movie.release_year }})
                                    </span>
                                </template>
                            </p>
                            <p
                                v-if="
                                    movie.tmdb_vote_average != null &&
                                    movie.tmdb_vote_count != null
                                "
                                class="mt-0.5 text-sm text-zinc-500"
                            >
                                TMDB:
                                {{
                                    Number(movie.tmdb_vote_average).toFixed(1)
                                }}/10 ({{
                                    Number(
                                        movie.tmdb_vote_count,
                                    ).toLocaleString()
                                }}
                                votes)
                            </p>
                        </div>

                        <!-- Filesystem curator review (editorial from markdown) -->
                        <div
                            v-if="hasFilesystemCuratorReview && curatorReview"
                            class="mt-6 space-y-3 lg:mt-0"
                            role="region"
                            aria-label="Editorial review"
                        >
                            <div
                                class="rounded-lg border border-zinc-800 bg-zinc-900/50 p-6"
                            >
                                <CuratorReviewComponent
                                    :review="curatorReview"
                                />
                            </div>
                        </div>

                        <!-- DB curator review (when no filesystem review) -->
                        <div
                            v-else-if="hasDbCuratorReview && curator_review"
                            class="mt-6 space-y-3 lg:mt-0"
                            role="region"
                            aria-label="Curator's review"
                        >
                            <h2 class="text-lg font-semibold text-white">
                                {{ curator_review.user?.name ?? 'Curator' }}'s
                                review
                            </h2>
                            <ReviewCard
                                :review="curator_review"
                                :hide-spoiler-blur="true"
                                :is-curator-pick="true"
                                :default-expanded="true"
                            />
                        </div>

                        <!-- No curator: "All reviews" lives in main column on desktop so grid isn't lopsided -->
                        <template
                            v-if="
                                !hasCuratorReview && !onlyCuratorNoUserReviews
                            "
                        >
                            <SectionHeader
                                title="All reviews"
                                :level="2"
                                class="mt-6 mb-4 lg:mt-0"
                            />
                            <ReviewList
                                :reviews="reviews"
                                :query-params="queryParams"
                                :movie-slug="movie.slug"
                                empty-message="No reviews yet. Be the first to share your thoughts!"
                            />
                            <!-- Write a review directly below All reviews -->
                            <div
                                v-if="showWriteReviewSection"
                                class="mt-6 space-y-4"
                            >
                                <Button
                                    v-if="!showReviewForm"
                                    variant="outline"
                                    class="border-zinc-700 text-zinc-300 hover:bg-zinc-800 hover:text-white focus-visible:ring-red-500"
                                    @click="showReviewForm = true"
                                >
                                    Write a review
                                </Button>
                                <div
                                    v-else
                                    class="rounded-lg border border-zinc-800 bg-zinc-900/50 p-6"
                                >
                                    <h2
                                        class="mb-4 text-lg font-semibold text-white"
                                    >
                                        Write a review
                                    </h2>
                                    <ReviewForm
                                        :movie="movie"
                                        :existing-review="null"
                                    />
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Sidebar: poster + movie facts (desktop only; second in DOM, placed left via grid) -->
                    <aside
                        class="min-w-0 space-y-4 lg:col-start-1 lg:row-start-1"
                        aria-label="Movie details"
                    >
                        <div class="hidden lg:block">
                            <Link
                                :href="`/movies/${movie.slug}`"
                                class="block focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                            >
                                <Poster
                                    :src="movie.poster_url"
                                    :alt="movie.title"
                                    :poster-path="movie.poster_path"
                                    context="grid"
                                    class="w-full rounded-lg"
                                />
                            </Link>
                        </div>
                        <MovieFacts :movie="movie" />
                    </aside>
                </div>

                <!-- Guest empty state: no reviews at all -->
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
                            class="text-sm font-medium text-red-500 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                        >
                            Sign up
                        </Link>
                    </div>
                </div>

                <!-- Only curator, no user reviews: single CTA block (no "More reviews" section) -->
                <div
                    v-else-if="onlyCuratorNoUserReviews"
                    class="rounded-lg border border-zinc-800 bg-zinc-900/50 p-6 text-center"
                >
                    <p class="mb-4 text-zinc-300">
                        No user reviews yet. Be the first to share your
                        thoughts!
                    </p>
                    <div
                        v-if="auth.user"
                        class="flex flex-wrap items-center justify-center gap-3"
                    >
                        <Button
                            v-if="!showReviewForm"
                            class="bg-red-600 text-white hover:bg-red-700 focus-visible:ring-red-500"
                            @click="showReviewForm = true"
                        >
                            Write a review
                        </Button>
                        <div
                            v-else
                            class="w-full rounded-lg border border-zinc-800 bg-zinc-950/60 p-4 text-left"
                        >
                            <h2 class="mb-4 text-lg font-semibold text-white">
                                Write a review
                            </h2>
                            <ReviewForm
                                :movie="movie"
                                :existing-review="null"
                            />
                        </div>
                    </div>
                    <div
                        v-else
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
                            class="text-sm font-medium text-red-500 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                        >
                            Sign up
                        </Link>
                    </div>
                </div>

                <!-- More reviews (below grid when curator present; "All reviews" when no curator is in main column above) -->
                <div v-else-if="hasCuratorReview">
                    <SectionHeader
                        title="More reviews"
                        :level="2"
                        class="mb-4"
                    />
                    <ReviewList
                        :reviews="reviews"
                        :query-params="queryParams"
                        :movie-slug="movie.slug"
                        empty-message="No other reviews yet."
                    />
                </div>

                <!-- Write review below "More reviews" when curator present (no-curator case has it in main column below All reviews) -->
                <div
                    v-if="showWriteReviewSection && hasCuratorReview"
                    class="space-y-4"
                >
                    <Button
                        v-if="!showReviewForm"
                        variant="outline"
                        class="border-zinc-700 text-zinc-300 hover:bg-zinc-800 hover:text-white focus-visible:ring-red-500"
                        @click="showReviewForm = true"
                    >
                        Write a review
                    </Button>
                    <div
                        v-else
                        class="rounded-lg border border-zinc-800 bg-zinc-900/50 p-6"
                    >
                        <h2 class="mb-4 text-lg font-semibold text-white">
                            Write a review
                        </h2>
                        <ReviewForm :movie="movie" :existing-review="null" />
                    </div>
                </div>
            </PublicContainer>
        </PublicSection>
    </PublicLayout>
</template>
