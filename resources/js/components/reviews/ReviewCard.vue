<script setup lang="ts">
import type { Review } from '@/types/models';

import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import { StarRating } from '@/components/primitives';
import ReviewContent from '@/components/reviews/ReviewContent.vue';

interface Props {
    review: Review;
    compact?: boolean;
    /** When true, content is never blurred (e.g. user's own review). */
    hideSpoilerBlur?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    compact: false,
    hideSpoilerBlur: false,
});

const emit = defineEmits<{
    edit: [];
    delete: [];
    'toggle-comments': [];
}>();

const spoilerRevealed = ref(false);
const contentExpanded = ref(false);

const isSpoilerBlurred = computed(
    () =>
        props.review.has_spoilers &&
        !props.hideSpoilerBlur &&
        !spoilerRevealed.value,
);

const hasMoreContent = computed(
    () =>
        !contentExpanded.value &&
        props.review.content_excerpt.length > 0 &&
        props.review.content_excerpt.endsWith('…'),
);

function revealSpoilers() {
    spoilerRevealed.value = true;
}
</script>

<template>
    <article
        class="rounded-lg border border-zinc-800 bg-zinc-900/50 p-6 transition-colors dark:border-zinc-800 dark:bg-zinc-900/50"
        data-testid="review-card"
    >
        <header class="mb-3 flex flex-wrap items-start justify-between gap-2">
            <div class="flex flex-wrap items-center gap-2">
                <StarRating :rating="review.rating" :max-stars="4" size="md" />
                <span
                    v-if="review.has_spoilers"
                    class="rounded bg-amber-500/20 px-2 py-0.5 text-xs font-medium text-amber-600 dark:text-amber-400"
                >
                    Contains spoilers
                </span>
            </div>
            <div class="text-sm text-zinc-400">
                <span v-if="review.user" class="font-medium text-zinc-300">
                    {{ review.user.name }}
                </span>
                <span class="text-zinc-500"> · </span>
                <span>{{ review.formatted_date }}</span>
                <span v-if="review.is_edited" class="text-zinc-500">
                    (edited)
                </span>
            </div>
        </header>

        <h3 v-if="review.title" class="mb-2 text-base font-semibold text-white">
            {{ review.title }}
        </h3>

        <div
            class="relative text-sm text-zinc-300"
            :class="[
                isSpoilerBlurred &&
                    'blur-md transition-all duration-300 select-none [user-select:none]',
            ]"
        >
            <ReviewContent
                :content-html="review.content_html"
                :content-excerpt="review.content_excerpt"
                :collapsed="!contentExpanded && !compact"
                size="sm"
            />
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

        <button
            v-if="hasMoreContent && !isSpoilerBlurred"
            type="button"
            class="mt-2 text-sm font-medium text-red-500 hover:underline focus:ring-2 focus:ring-red-500 focus:outline-none"
            @click="contentExpanded = true"
        >
            Read more
        </button>

        <footer
            class="mt-4 flex flex-wrap items-center gap-3 border-t border-zinc-800 pt-3"
        >
            <button
                v-if="review.can_edit"
                type="button"
                class="text-sm text-zinc-400 hover:text-white focus:ring-2 focus:ring-red-500 focus:outline-none"
                @click="emit('edit')"
            >
                Edit
            </button>
            <button
                v-if="review.can_delete"
                type="button"
                class="text-sm text-zinc-400 hover:text-red-500 focus:ring-2 focus:ring-red-500 focus:outline-none"
                @click="emit('delete')"
            >
                Delete
            </button>
            <Link
                v-if="review.id"
                :href="`/reviews/${review.id}`"
                class="text-sm text-zinc-400 hover:text-white focus:ring-2 focus:ring-red-500 focus:outline-none"
            >
                {{ review.comments_count }} comment{{
                    review.comments_count !== 1 ? 's' : ''
                }}
            </Link>
        </footer>
    </article>
</template>
