<script setup lang="ts">
import type { PaginationMeta, Review } from '@/types/models';

import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import Pagination from '@/components/Pagination.vue';
import ReviewCard from '@/components/reviews/ReviewCard.vue';

interface Props {
    reviews: {
        data: Review[];
        meta: PaginationMeta;
        links: { url: string | null; label: string; active: boolean }[];
    };
    queryParams?: { show_spoilers?: boolean; sort?: string } | undefined;
    movieSlug?: string;
    /** Override empty state message (e.g. "No other reviews yet" when curator is pinned above). */
    emptyMessage?: string;
}

const props = withDefaults(defineProps<Props>(), {
    queryParams: () => ({}),
    movieSlug: '',
    emptyMessage: 'No reviews yet. Be the first to share your thoughts!',
});

const emit = defineEmits<{
    edit: [review: Review];
    delete: [review: Review];
}>();

const hasReviews = computed(() => props.reviews.data.length > 0);

function buildReviewsUrl(
    overrides: Record<string, string | boolean | undefined>,
) {
    if (!props.movieSlug) return '#';
    const params = new URLSearchParams();
    const merged = { ...props.queryParams, ...overrides };
    if (merged.show_spoilers) params.set('show_spoilers', '1');
    if (merged.sort) params.set('sort', merged.sort);
    const query = params.toString();
    return `/movies/${props.movieSlug}/reviews${query ? `?${query}` : ''}`;
}
</script>

<template>
    <div class="space-y-6">
        <div
            v-if="hasReviews"
            class="flex flex-wrap items-center justify-between gap-4"
        >
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm text-[--intel-text-muted]">Show:</span>
                <Link
                    :href="buildReviewsUrl({ show_spoilers: false })"
                    :class="[
                        'rounded px-3 py-1.5 text-sm transition-colors',
                        !queryParams?.show_spoilers
                            ? 'bg-[--intel-bg-elevated] text-[--intel-text-primary]'
                            : 'text-[--intel-text-muted] hover:text-[--intel-text-primary]',
                    ]"
                >
                    No spoilers
                </Link>
                <Link
                    :href="buildReviewsUrl({ show_spoilers: true })"
                    :class="[
                        'rounded px-3 py-1.5 text-sm transition-colors',
                        queryParams?.show_spoilers
                            ? 'bg-[--intel-bg-elevated] text-[--intel-text-primary]'
                            : 'text-[--intel-text-muted] hover:text-[--intel-text-primary]',
                    ]"
                >
                    Include spoilers
                </Link>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm text-[--intel-text-muted]">Sort:</span>
                <Link
                    :href="buildReviewsUrl({ sort: 'recent' })"
                    :class="[
                        'rounded px-3 py-1.5 text-sm transition-colors',
                        !queryParams?.sort || queryParams?.sort === 'recent'
                            ? 'bg-[--intel-bg-elevated] text-[--intel-text-primary]'
                            : 'text-[--intel-text-muted] hover:text-[--intel-text-primary]',
                    ]"
                >
                    Recent
                </Link>
                <Link
                    :href="buildReviewsUrl({ sort: 'helpful' })"
                    :class="[
                        'rounded px-3 py-1.5 text-sm transition-colors',
                        queryParams?.sort === 'helpful'
                            ? 'bg-[--intel-bg-elevated] text-[--intel-text-primary]'
                            : 'text-[--intel-text-muted] hover:text-[--intel-text-primary]',
                    ]"
                >
                    Helpful
                </Link>
                <Link
                    :href="buildReviewsUrl({ sort: 'rating_high' })"
                    :class="[
                        'rounded px-3 py-1.5 text-sm transition-colors',
                        queryParams?.sort === 'rating_high'
                            ? 'bg-[--intel-bg-elevated] text-[--intel-text-primary]'
                            : 'text-[--intel-text-muted] hover:text-[--intel-text-primary]',
                    ]"
                >
                    Highest rated
                </Link>
                <Link
                    :href="buildReviewsUrl({ sort: 'rating_low' })"
                    :class="[
                        'rounded px-3 py-1.5 text-sm transition-colors',
                        queryParams?.sort === 'rating_low'
                            ? 'bg-[--intel-bg-elevated] text-[--intel-text-primary]'
                            : 'text-[--intel-text-muted] hover:text-[--intel-text-primary]',
                    ]"
                >
                    Lowest rated
                </Link>
            </div>
        </div>

        <div v-if="hasReviews" class="flex flex-col gap-4">
            <ReviewCard
                v-for="review in reviews.data"
                :key="review.id"
                :review="review"
                :hide-spoiler-blur="false"
                @edit="emit('edit', review)"
                @delete="emit('delete', review)"
            />
        </div>

        <div
            v-else
            class="rounded-lg border border-[--intel-border] bg-[--intel-bg-surface]/50 p-12 text-center"
        >
            <p class="text-[--intel-text-muted]">{{ emptyMessage }}</p>
        </div>

        <Pagination
            v-if="reviews.meta && reviews.meta.last_page > 1"
            :meta="reviews.meta"
        />
    </div>
</template>
