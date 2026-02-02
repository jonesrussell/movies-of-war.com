<script setup lang="ts">
import type { Movie, Review } from '@/types/models';

import { Form } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

import InputError from '@/components/InputError.vue';
import { StarRating } from '@/components/primitives';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/movies/reviews';
import { update } from '@/routes/reviews';

interface Props {
    movie: Movie;
    existingReview?: Review | null;
}

const props = defineProps<Props>();

const rating = ref(0);

onMounted(() => {
    rating.value = props.existingReview?.rating ?? 0;
});

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
                placeholder="e.g. Midway (1976) — ★★"
                class="bg-zinc-900 text-white dark:bg-zinc-900 dark:text-white"
            />
            <InputError :message="errors.title" />
        </div>

        <div class="space-y-2">
            <Label for="content">Your review (at least 50 characters)</Label>
            <textarea
                id="content"
                name="content"
                rows="8"
                required
                minlength="50"
                :default-value="existingReview?.content ?? ''"
                placeholder="Share your thoughts on this film..."
                class="flex min-h-[8rem] w-full rounded-md border border-zinc-800 bg-zinc-900 px-3 py-2 text-sm text-white placeholder:text-zinc-500 focus:ring-2 focus:ring-red-500 focus:outline-none disabled:pointer-events-none disabled:opacity-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-white"
            />
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
