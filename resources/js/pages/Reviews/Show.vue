<script setup lang="ts">
import type { Review } from '@/types/models';

import { Head, Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import { StarRating } from '@/components/primitives';
import PublicContainer from '@/components/public/PublicContainer.vue';
import PublicSection from '@/components/public/PublicSection.vue';
import ReviewComments from '@/components/reviews/ReviewComments.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';

interface Props {
    review: Review;
}

const props = defineProps<Props>();

const page = usePage();
const auth = page.props.auth;

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
        <Head :title="`Review: ${review.title ?? 'Review'}`" />

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
                        <div
                            class="break-words whitespace-pre-wrap text-zinc-300"
                        >
                            {{ review.content }}
                        </div>
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
