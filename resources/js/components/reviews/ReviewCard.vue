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
    /** When true, shows "Curator's pick" styling and badge. */
    isCuratorPick?: boolean;
    /** When true, show full review content by default (e.g. featured curator review on reviews page). */
    defaultExpanded?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    compact: false,
    hideSpoilerBlur: false,
    isCuratorPick: false,
    defaultExpanded: false,
});

const emit = defineEmits<{
    edit: [];
    delete: [];
    'toggle-comments': [];
}>();

const spoilerRevealed = ref(false);

const isSpoilerBlurred = computed(
    () =>
        props.review.has_spoilers &&
        !props.hideSpoilerBlur &&
        !spoilerRevealed.value,
);

function revealSpoilers() {
    spoilerRevealed.value = true;
}
</script>

<template>
    <article
        class="rounded-lg border p-6 transition-colors"
        :class="
            isCuratorPick
                ? 'border-[--intel-alert]/50 bg-[--intel-bg-surface]/80 ring-2 ring-[--intel-alert]/30'
                : 'border-[--intel-border] bg-[--intel-bg-surface]/50'
        "
        data-testid="review-card"
        :aria-label="isCuratorPick ? 'Curator\'s pick review' : undefined"
    >
        <header class="mb-3 flex flex-wrap items-start justify-between gap-2">
            <div class="flex flex-wrap items-center gap-2">
                <span
                    v-if="isCuratorPick"
                    class="rounded bg-[--intel-alert]/90 px-2.5 py-0.5 text-xs font-semibold tracking-wide text-white uppercase"
                >
                    Curator's pick
                </span>
                <StarRating :rating="review.rating" :max-stars="4" size="md" />
                <span
                    v-if="review.has_spoilers"
                    class="rounded bg-amber-500/20 px-2 py-0.5 text-xs font-medium text-amber-600 dark:text-amber-400"
                >
                    Contains spoilers
                </span>
            </div>
            <div class="text-sm text-[--intel-text-muted]">
                <span
                    v-if="review.user"
                    class="font-medium text-[--intel-text-body]"
                >
                    {{ review.user.name }}
                </span>
                <span class="text-[--intel-text-faint]"> · </span>
                <span>{{ review.formatted_date }}</span>
                <span v-if="review.is_edited" class="text-[--intel-text-faint]">
                    (edited)
                </span>
            </div>
        </header>

        <h3
            v-if="review.title"
            class="mb-2 text-base font-semibold text-[--intel-text-primary]"
        >
            {{ review.title }}
        </h3>

        <div
            class="relative text-[--intel-text-body]"
            :class="[
                isSpoilerBlurred &&
                    'blur-md transition-all duration-300 select-none [user-select:none]',
            ]"
        >
            <ReviewContent
                :content-html="review.content_html"
                :content-excerpt="review.content_excerpt"
                :collapsed="false"
                size="sm"
            />
            <div
                v-if="isSpoilerBlurred"
                class="absolute inset-0 flex items-center justify-center bg-[--intel-bg-surface]/80"
            >
                <button
                    type="button"
                    class="rounded-lg border border-[--intel-border-bright] bg-[--intel-bg-elevated] px-4 py-2 text-sm font-medium text-[--intel-text-primary] transition-colors hover:bg-[--intel-border] focus:ring-2 focus:ring-[--intel-alert] focus:outline-none"
                    @click="revealSpoilers"
                >
                    Reveal spoilers
                </button>
            </div>
        </div>

        <footer
            class="mt-4 flex flex-wrap items-center gap-3 border-t border-[--intel-border] pt-3"
        >
            <button
                v-if="review.can_edit"
                type="button"
                class="text-sm text-[--intel-text-muted] hover:text-[--intel-text-primary] focus:ring-2 focus:ring-[--intel-alert] focus:outline-none"
                @click="emit('edit')"
            >
                Edit
            </button>
            <button
                v-if="review.can_delete"
                type="button"
                class="text-sm text-[--intel-text-muted] hover:text-[--intel-alert] focus:ring-2 focus:ring-[--intel-alert] focus:outline-none"
                @click="emit('delete')"
            >
                Delete
            </button>
            <Link
                v-if="review.id"
                :href="`/reviews/${review.id}`"
                class="text-sm text-[--intel-text-muted] hover:text-[--intel-text-primary] focus:ring-2 focus:ring-[--intel-alert] focus:outline-none"
            >
                {{ review.comments_count }} comment{{
                    review.comments_count !== 1 ? 's' : ''
                }}
            </Link>
        </footer>
    </article>
</template>
