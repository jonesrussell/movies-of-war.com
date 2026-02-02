<script setup lang="ts">
import type { Movie, Review } from '@/types/models';

import { Form } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

import InputError from '@/components/InputError.vue';
import { StarRating } from '@/components/primitives';
import ReviewContent from '@/components/reviews/ReviewContent.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { preview as markdownPreview } from '@/routes/markdown';
import { store } from '@/routes/movies/reviews';
import { update } from '@/routes/reviews';

interface Props {
    movie: Movie;
    existingReview?: Review | null;
}

const props = defineProps<Props>();

const rating = ref(0);
const activeTab = ref<'write' | 'preview'>('write');
const contentRef = ref<HTMLTextAreaElement | null>(null);
const previewHtml = ref('');
const previewLoading = ref(false);

onMounted(() => {
    rating.value = props.existingReview?.rating ?? 0;
});

watch(activeTab, (tab) => {
    if (tab === 'preview' && contentRef.value) {
        void fetchPreview(contentRef.value.value);
    }
});

async function fetchPreview(markdown: string) {
    previewLoading.value = true;
    previewHtml.value = '';
    try {
        const token = document.cookie
            .split('; ')
            .find((row) => row.startsWith('XSRF-TOKEN='))
            ?.split('=')[1];
        const res = await fetch(markdownPreview.url(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                ...(token && { 'X-XSRF-TOKEN': decodeURIComponent(token) }),
            },
            body: JSON.stringify({ markdown: markdown || '' }),
            credentials: 'same-origin',
        });
        const data = (await res.json()) as { html?: string };
        previewHtml.value = data.html ?? '';
    } finally {
        previewLoading.value = false;
    }
}

const formAction = computed(() => {
    if (props.existingReview) {
        return update.url({ review: props.existingReview.id });
    }
    return store.url({ movie: props.movie.slug });
});

const formMethod = computed(() => (props.existingReview ? 'patch' : 'post'));

const submitLabel = computed(() =>
    props.existingReview ? 'Update review' : 'Publish review',
);
</script>

<template>
    <Form
        :action="formAction"
        :method="formMethod"
        v-slot="{ errors, processing }"
        class="space-y-4"
    >
        <div class="space-y-2">
            <Label for="rating">Your rating</Label>
            <div class="flex items-center gap-2">
                <StarRating
                    v-model:rating="rating"
                    :max-stars="4"
                    interactive
                    size="lg"
                />
                <input
                    id="rating"
                    type="hidden"
                    name="rating"
                    :value="rating"
                />
            </div>
            <InputError :message="errors.rating" />
        </div>

        <div class="space-y-2">
            <Label for="title">Title (optional)</Label>
            <Input
                id="title"
                type="text"
                name="title"
                :default-value="existingReview?.title ?? ''"
                maxlength="255"
                placeholder="e.g. Midway (1976)"
                class="bg-zinc-900 text-white dark:bg-zinc-900 dark:text-white"
            />
            <InputError :message="errors.title" />
        </div>

        <div class="space-y-2">
            <div class="flex items-center justify-between gap-2">
                <Label for="content"
                    >Your review (at least 50 characters)</Label
                >
                <div class="flex rounded-lg border border-zinc-800 p-0.5">
                    <button
                        type="button"
                        class="rounded-md px-2 py-1 text-xs font-medium transition-colors"
                        :class="
                            activeTab === 'write'
                                ? 'bg-zinc-700 text-white'
                                : 'text-zinc-400 hover:text-zinc-300'
                        "
                        @click="activeTab = 'write'"
                    >
                        Write
                    </button>
                    <button
                        type="button"
                        class="rounded-md px-2 py-1 text-xs font-medium transition-colors"
                        :class="
                            activeTab === 'preview'
                                ? 'bg-zinc-700 text-white'
                                : 'text-zinc-400 hover:text-zinc-300'
                        "
                        @click="activeTab = 'preview'"
                    >
                        Preview
                    </button>
                </div>
            </div>
            <p class="text-xs text-zinc-500">
                Markdown supported: **bold**, *italic*, [link](url), lists,
                blockquotes. No raw HTML.
            </p>
            <div v-show="activeTab === 'write'">
                <textarea
                    id="content"
                    ref="contentRef"
                    name="content"
                    rows="8"
                    required
                    minlength="50"
                    :default-value="existingReview?.content ?? ''"
                    placeholder="Share your thoughts on this film..."
                    class="flex min-h-[8rem] w-full rounded-md border border-zinc-800 bg-zinc-900 px-3 py-2 text-sm text-white placeholder:text-zinc-500 focus:ring-2 focus:ring-red-500 focus:outline-none disabled:pointer-events-none disabled:opacity-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-white"
                />
            </div>
            <div
                v-show="activeTab === 'preview'"
                class="min-h-[8rem] rounded-md border border-zinc-800 bg-zinc-900 px-3 py-2"
            >
                <div
                    v-if="previewLoading"
                    class="flex items-center gap-2 text-sm text-zinc-500"
                >
                    <Spinner class="size-4" />
                    Loading preview…
                </div>
                <ReviewContent
                    v-else-if="previewHtml"
                    :content-html="previewHtml"
                    size="sm"
                />
                <p v-else class="text-sm text-zinc-500">
                    Nothing to preview yet.
                </p>
            </div>
            <InputError :message="errors.content" />
        </div>

        <div class="flex items-center gap-2">
            <input
                id="has_spoilers"
                type="checkbox"
                name="has_spoilers"
                value="1"
                :checked="existingReview?.has_spoilers ?? false"
                class="h-4 w-4 rounded border-zinc-700 bg-zinc-900 text-red-500 focus:ring-red-500"
            />
            <Label
                for="has_spoilers"
                class="cursor-pointer text-sm font-normal"
            >
                This review contains spoilers
            </Label>
        </div>
        <InputError :message="errors.has_spoilers" />

        <div class="flex gap-2">
            <Button
                type="submit"
                :disabled="processing || rating === 0"
                class="bg-red-600 hover:bg-red-700 dark:bg-red-600 dark:hover:bg-red-700"
            >
                <Spinner v-if="processing" class="mr-2 size-4" />
                {{ submitLabel }}
            </Button>
        </div>
    </Form>
</template>
