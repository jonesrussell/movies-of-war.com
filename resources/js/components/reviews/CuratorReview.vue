<script setup lang="ts">
import type { FilesystemCuratorReview } from '@/types';

import { onMounted, ref } from 'vue';

import { StarRating } from '@/components/primitives';

interface Props {
    review: FilesystemCuratorReview;
}

defineProps<Props>();

const reviewEl = ref<HTMLElement | null>(null);

onMounted(() => {
    if (!reviewEl.value) return;

    // Activate spoiler blocks on click
    reviewEl.value
        .querySelectorAll<HTMLElement>('.spoiler-block')
        .forEach((el) => {
            el.addEventListener('click', () => {
                el.classList.toggle('revealed');
            });
            el.addEventListener('keydown', (e: KeyboardEvent) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    el.classList.toggle('revealed');
                }
            });
        });
});
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-wrap items-center gap-3">
            <StarRating :rating="review.rating" :max-stars="4" size="lg" />
            <span class="text-sm font-medium text-zinc-400">
                Curator's Review
            </span>
        </div>

        <div
            v-if="review.has_spoilers"
            class="rounded-md bg-amber-900/20 px-3 py-2 text-sm text-amber-400"
        >
            This review contains spoilers.
        </div>

        <div
            ref="reviewEl"
            class="review-content prose prose-lg leading-relaxed break-words text-zinc-300 prose-neutral dark:prose-invert prose-headings:text-white prose-p:text-zinc-300 prose-a:text-red-500 prose-a:no-underline hover:prose-a:underline prose-strong:text-white prose-hr:border-zinc-700"
            <!-- eslint-disable-next-line vue/no-v-html -- curator content is sanitized server-side -->
            v-html="review.content_html"
        />
    </div>
</template>
