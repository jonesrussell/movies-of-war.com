<script setup lang="ts">
import type { AppPageProps, Movie } from '@/types';
import type { PaginationMeta, Review } from '@/types/models';

import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import { Poster } from '@/components/primitives';
import PublicContainer from '@/components/public/PublicContainer.vue';
import PublicSection from '@/components/public/PublicSection.vue';
import SectionHeader from '@/components/public/SectionHeader.vue';
import ReviewCard from '@/components/reviews/ReviewCard.vue';
import ReviewForm from '@/components/reviews/ReviewForm.vue';
import ReviewList from '@/components/reviews/ReviewList.vue';
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { login, register } from '@/routes';

interface Props {
    movie: Movie;
    curator_review?: Review | null;
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

const hasCuratorReview = computed(() => Boolean(props.curator_review));
const hasNoReviews = computed(
    () => !hasCuratorReview.value && (props.reviews?.data?.length ?? 0) === 0,
);
const loginUrl = computed(
    () =>
        login({ query: { redirect: `/movies/${props.movie.slug}/reviews` } })
            .url,
);
</script>

<template>
    <PublicLayout>
        <Head :title="`Reviews – ${movie.title}`" />

        <PublicSection spacing="md">
            <PublicContainer class="flex flex-col gap-6">
                <Link
                    :href="`/movies/${movie.slug}`"
                    class="inline-flex items-center gap-2 text-sm text-zinc-400 transition-colors hover:text-white"
                >
                    <ArrowLeft class="size-4" />
                    Back to {{ movie.title }}
                </Link>

                <!-- Compact movie context -->
                <div
                    class="flex items-center gap-4 rounded-lg border border-zinc-800 bg-zinc-900/50 p-4"
                >
                    <Link
                        :href="`/movies/${movie.slug}`"
                        class="shrink-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                    >
                        <Poster
                            :src="movie.poster_url"
                            :alt="movie.title"
                            :poster-path="movie.poster_path"
                            context="grid"
                            class="rounded-lg"
                        />
                    </Link>
                    <div class="min-w-0 flex-1">
                        <Link
                            :href="`/movies/${movie.slug}`"
                            class="text-lg font-semibold text-white transition-colors hover:text-red-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                        >
                            {{ movie.title }}
                        </Link>
                        <p
                            v-if="movie.release_year"
                            class="mt-0.5 text-sm text-zinc-400"
                        >
                            {{ movie.release_year }}
                        </p>
                        <p
                            v-if="
                                movie.tmdb_vote_average != null &&
                                movie.tmdb_vote_count != null
                            "
                            class="mt-1 text-sm text-zinc-500"
                        >
                            TMDB:
                            {{ Number(movie.tmdb_vote_average).toFixed(1) }}/10
                            ({{
                                Number(movie.tmdb_vote_count).toLocaleString()
                            }}
                            votes)
                        </p>
                    </div>
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
                            class="text-sm font-medium text-red-500 hover:underline"
                        >
                            Sign up
                        </Link>
                    </div>
                </div>

                <!-- Curator review (featured, always first) -->
                <div
                    v-if="hasCuratorReview && curator_review"
                    class="space-y-3"
                    role="region"
                    aria-label="Featured review"
                >
                    <h2 class="text-lg font-semibold text-white">
                        {{ curator_review.user?.name ?? 'Curator' }}'s review
                    </h2>
                    <ReviewCard
                        :review="curator_review"
                        :hide-spoiler-blur="true"
                        :is-curator-pick="true"
                        :default-expanded="true"
                    />
                </div>

                <!-- More reviews (or All reviews when no curator) -->
                <div>
                    <SectionHeader
                        :title="
                            hasCuratorReview ? 'More reviews' : 'All reviews'
                        "
                        :level="2"
                        class="mb-4"
                    />
                    <ReviewList
                        :reviews="reviews"
                        :query-params="queryParams"
                        :movie-slug="movie.slug"
                        :empty-message="
                            hasCuratorReview
                                ? 'No other reviews yet.'
                                : undefined
                        "
                    />
                </div>

                <!-- Write review (authenticated only) -->
                <div v-if="auth.user" class="space-y-4">
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
