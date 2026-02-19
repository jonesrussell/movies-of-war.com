<script setup lang="ts">
import type { Movie } from '@/types';

import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import { Poster, StarRating } from '@/components/primitives';
import UpcomingBadge from '@/components/UpcomingBadge.vue';
import { formatReleaseDate } from '@/utils/format';

interface Props {
    movie: Movie;
    showOverlay?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showOverlay: true,
});

const displayedTags = computed(() => {
    const tags = props.movie.tags;
    if (!tags || !Array.isArray(tags) || typeof tags.slice !== 'function') {
        return [];
    }
    return tags.slice(0, 3);
});
</script>

<template>
    <article
        class="group flex flex-col gap-2 rounded-md border border-[--intel-border] bg-[--intel-bg-surface] transition-all duration-200 hover:border-[--intel-accent] hover:shadow-[0_0_12px_var(--intel-accent-glow)]"
    >
        <div class="flex flex-col gap-2">
            <Link
                :href="`/movies/${movie.slug}`"
                :aria-label="`View details for ${movie.title} (${movie.release_year})`"
                class="relative block overflow-hidden rounded-sm transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
            >
                <Poster
                    :src="movie.poster_url"
                    :alt="movie.title"
                    :poster-path="movie.poster_path"
                    context="grid"
                    aspect-ratio="2/3"
                    class="rounded-sm transition-transform duration-500 [transition-timing-function:var(--ease-smooth-out)] group-hover:scale-[1.03]"
                >
                    <!-- Upcoming badge -->
                    <div
                        v-if="movie.is_upcoming"
                        class="absolute top-3 right-3 z-20"
                    >
                        <UpcomingBadge />
                    </div>

                    <!-- Overlay slot for actions -->
                    <slot v-if="showOverlay" name="overlay" />
                </Poster>
            </Link>

            <!-- Movie details - always visible -->
            <div class="px-2 pb-2">
                <h3
                    class="mb-1 truncate text-sm font-semibold text-[--intel-text-primary]"
                >
                    <Link
                        :href="`/movies/${movie.slug}`"
                        class="transition-colors hover:text-blue-400"
                    >
                        {{ movie.title }}
                    </Link>
                </h3>

                <div
                    class="flex items-center gap-2 text-xs text-[--intel-text-muted]"
                >
                    <span>{{
                        formatReleaseDate(
                            movie.release_date,
                            movie.release_year,
                        )
                    }}</span>
                    <template v-if="movie.runtime">
                        <span class="text-[--intel-text-faint]">•</span>
                        <span>{{ movie.runtime }} min</span>
                    </template>
                    <template v-if="movie.country">
                        <span class="text-[--intel-text-faint]">•</span>
                        <span>{{ movie.country }}</span>
                    </template>
                </div>

                <div
                    v-if="displayedTags.length > 0"
                    class="mt-2 flex flex-wrap gap-1"
                >
                    <span
                        v-for="tag in displayedTags"
                        :key="tag.id"
                        class="rounded-sm border border-[--intel-border] bg-[--intel-bg-elevated] px-2 py-0.5 font-[family-name:var(--font-mono-display)] text-xs text-[--intel-text-body]"
                    >
                        {{ tag.name }}
                    </span>
                </div>

                <div
                    v-if="movie.user_review"
                    class="mt-2 flex flex-wrap items-center gap-1.5 text-xs text-[--intel-text-body]"
                >
                    <span
                        class="font-[family-name:var(--font-mono-display)] text-[--intel-text-muted]"
                        >Your rating:</span
                    >
                    <StarRating
                        :rating="movie.user_review.rating"
                        :max-stars="4"
                        size="sm"
                    />
                    <Link
                        :href="`/movies/${movie.slug}#reviews`"
                        class="font-medium text-blue-500 transition-colors hover:text-blue-400"
                    >
                        Your review
                    </Link>
                </div>
            </div>
        </div>

        <div v-if="$slots.actions">
            <slot name="actions" />
        </div>
    </article>
</template>
