<script setup lang="ts">
import type { Review } from '@/types/models';

import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import JsonLdScript from '@/components/JsonLdScript.vue';
import { StarRating } from '@/components/primitives';
import PublicContainer from '@/components/public/PublicContainer.vue';
import PublicSection from '@/components/public/PublicSection.vue';
import ReviewComments from '@/components/reviews/ReviewComments.vue';
import ReviewContent from '@/components/reviews/ReviewContent.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';

interface Props {
    review: Review;
}

const props = defineProps<Props>();

const page = usePage();
const auth = page.props.auth;

const appUrl = computed(() => (page.props.appUrl as string) ?? '');
const canonicalUrl = computed(() =>
    appUrl.value && page.url ? `${appUrl.value}${page.url}` : '',
);
const reviewDescription = computed(
    () =>
        props.review.content_excerpt ||
        `Review of ${props.review.movie?.title ?? 'film'}`,
);
const ogImage = computed(() => {
    const url = props.review.movie?.poster_url;
    if (!url)
        return appUrl.value
            ? `${appUrl.value}/images/placeholders/poster-placeholder.png`
            : '';
    return url.startsWith('http') ? url : `${appUrl.value}${url}`;
});
const ogTitle = computed(() =>
    props.review.title
        ? `Review: ${props.review.title}`
        : `Review of ${props.review.movie?.title ?? 'film'}`,
);

const jsonLdReview = computed(() => {
    const r = props.review;
    const movie = r.movie;
    const movieUrl =
        appUrl.value && movie?.slug
            ? `${appUrl.value}/movies/${movie.slug}`
            : '';
    return JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'Review',
        itemReviewed: movie
            ? {
                  '@type': 'Movie',
                  name: movie.title,
                  url: movieUrl,
              }
            : undefined,
        author: {
            '@type': 'Person',
            name: r.user?.name ?? 'Curator',
        },
        reviewRating: {
            '@type': 'Rating',
            ratingValue: r.rating,
            bestRating: 4,
        },
        reviewBody: r.content_excerpt || undefined,
    });
});

const jsonLdBreadcrumb = computed(() => {
    const movie = props.review.movie;
    if (!appUrl.value || !movie?.slug) return '{}';
    return JSON.stringify({
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
                name: movie.title,
                item: `${appUrl.value}/movies/${movie.slug}`,
            },
            {
                '@type': 'ListItem',
                position: 4,
                name: 'Review',
                item: canonicalUrl.value,
            },
        ],
    });
});

const spoilerRevealed = ref(false);

const isSpoilerBlurred = computed(
    () => props.review.has_spoilers && !spoilerRevealed.value,
);

function revealSpoilers() {
    spoilerRevealed.value = true;
}
</script>

<template>
    <PublicLayout>
        <Head
            :title="
                review.title
                    ? `Review: ${review.title}`
                    : `Review of ${review.movie?.title ?? 'film'}`
            "
        >
            <meta
                head-key="description"
                name="description"
                :content="reviewDescription"
            />
            <link head-key="canonical" rel="canonical" :href="canonicalUrl" />
            <meta property="og:type" content="article" />
            <meta property="og:title" :content="ogTitle" />
            <meta property="og:description" :content="reviewDescription" />
            <meta property="og:image" :content="ogImage" />
            <meta property="og:url" :content="canonicalUrl" />
            <meta property="og:site_name" content="Movies of War" />
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:title" :content="ogTitle" />
            <meta name="twitter:description" :content="reviewDescription" />
            <meta name="twitter:image" :content="ogImage" />
        </Head>

        <JsonLdScript :content="jsonLdReview" />
        <JsonLdScript :content="jsonLdBreadcrumb" />

        <PublicSection spacing="md">
            <PublicContainer class="flex flex-col gap-6">
                <Link
                    v-if="review.movie?.slug"
                    :href="`/movies/${review.movie.slug}`"
                    class="inline-flex items-center gap-2 text-sm text-zinc-400 transition-colors hover:text-white"
                >
                    <ArrowLeft class="size-4" />
                    Back to {{ review.movie.title ?? 'film' }}
                </Link>

                <article
                    class="rounded-lg border border-zinc-800 bg-zinc-900/50 p-6"
                >
                    <header
                        class="mb-4 flex flex-wrap items-start justify-between gap-4"
                    >
                        <div class="flex flex-wrap items-center gap-2">
                            <StarRating
                                :rating="review.rating"
                                :max-stars="4"
                                size="lg"
                            />
                            <span
                                v-if="review.has_spoilers"
                                class="rounded bg-amber-500/20 px-2 py-0.5 text-xs font-medium text-amber-600 dark:text-amber-400"
                            >
                                Contains spoilers
                            </span>
                        </div>
                        <div class="text-sm text-zinc-400">
                            <span
                                v-if="review.user"
                                class="font-medium text-zinc-300"
                            >
                                {{ review.user.name }}
                            </span>
                            <span class="text-zinc-500"> · </span>
                            <span>{{ review.formatted_date }}</span>
                            <span v-if="review.is_edited" class="text-zinc-500">
                                (edited)
                            </span>
                        </div>
                    </header>

                    <h1
                        v-if="review.title"
                        class="mb-4 text-xl font-bold text-white"
                    >
                        {{ review.title }}
                    </h1>

                    <div
                        class="relative"
                        :class="[
                            isSpoilerBlurred &&
                                'blur-md transition-all duration-300 select-none [user-select:none]',
                        ]"
                    >
                        <ReviewContent :content-html="review.content_html" />
                        <div
                            v-if="isSpoilerBlurred"
                            class="absolute inset-0 flex items-center justify-center bg-zinc-900/80"
                        >
                            <button
                                type="button"
                                class="rounded-lg border border-zinc-700 bg-zinc-800 px-4 py-2 text-sm font-medium text-zinc-200 transition-colors hover:bg-zinc-700 focus:ring-2 focus:ring-red-500 focus:outline-none"
                                @click="revealSpoilers"
                            >
                                Reveal spoilers
                            </button>
                        </div>
                    </div>

                    <ReviewComments
                        :review="review"
                        :can-comment="Boolean(auth?.user)"
                    />
                </article>
            </PublicContainer>
        </PublicSection>
    </PublicLayout>
</template>
