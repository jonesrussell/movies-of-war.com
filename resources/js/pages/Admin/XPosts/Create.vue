<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Plus, Trash2, X } from 'lucide-vue-next';
import { computed } from 'vue';

import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import XPostPreviewCard from '@/components/dashboard/XPostPreviewCard.vue';
import DateTimePicker from '@/components/ui/DateTimePicker.vue';

interface Props {
    maxTweetLength: number;
}

const props = defineProps<Props>();

const form = useForm({
    content: '',
    thread_parts: [] as string[],
    media_urls: [] as string[],
    status: 'draft' as 'draft' | 'scheduled' | 'publish_immediately',
    scheduled_for: '',
    publish_immediately: false,
});

const characterCount = computed(
    () => props.maxTweetLength - (form.content?.length || 0),
);
const threadCharacterCounts = computed(() =>
    form.thread_parts.map((part) => props.maxTweetLength - part.length),
);

const isScheduled = computed(() => form.status === 'scheduled');
const isPublishImmediately = computed(
    () => form.status === 'publish_immediately',
);

function addThreadPart() {
    if (form.thread_parts.length < 25) {
        form.thread_parts.push('');
    }
}

function removeThreadPart(index: number) {
    form.thread_parts.splice(index, 1);
}

function addMediaUrl() {
    if (form.media_urls.length < 4) {
        form.media_urls.push('');
    }
}

function removeMediaUrl(index: number) {
    form.media_urls.splice(index, 1);
}

function submit() {
    const publishImmediately = form.status === 'publish_immediately';

    form.transform(() => ({
        ...form.data(),
        publish_immediately: publishImmediately,
        status: publishImmediately ? 'draft' : form.status,
    })).post('/x-posts', {
        onSuccess: () => {
            router.visit('/x-posts');
        },
    });
}
</script>

<template>
    <AppSidebarLayout>
        <Head title="Create X Post - Admin" />

        <div class="w-full px-4 py-12 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-3xl">
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-white">Create X Post</h1>
                    <p class="mt-2 text-zinc-400">
                        Create a new X (Twitter) post or schedule it for later
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Preview -->
                    <div>
                        <h3 class="mb-2 text-sm font-medium text-zinc-400">
                            How it looks on X
                        </h3>
                        <XPostPreviewCard
                            :content="form.content"
                            :media-urls="form.media_urls"
                            :media-overlay-text="
                                form.content?.match(/^[^(]+\(\d{4}\)/)
                                    ?.at(0) ?? null
                            "
                        />
                    </div>

                    <!-- Main Content -->
                    <div>
                        <label
                            for="content"
                            class="block text-sm font-medium text-zinc-300"
                        >
                            Tweet Content
                            <span class="text-zinc-500"
                                >({{ characterCount }} remaining)</span
                            >
                        </label>
                        <textarea
                            id="content"
                            v-model="form.content"
                            rows="4"
                            :maxlength="maxTweetLength"
                            placeholder="What's happening?"
                            class="mt-1 w-full rounded-lg border-zinc-700 bg-zinc-900 px-4 py-2 text-white placeholder-zinc-500 focus:border-red-500 focus:ring-red-500"
                        />
                        <div
                            v-if="form.errors.content"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ form.errors.content }}
                        </div>
                    </div>

                    <!-- Thread Parts -->
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <label
                                class="block text-sm font-medium text-zinc-300"
                            >
                                Thread Parts
                                <span class="text-zinc-500"
                                    >({{ form.thread_parts.length }}/25)</span
                                >
                            </label>
                            <button
                                v-if="form.thread_parts.length < 25"
                                type="button"
                                @click="addThreadPart"
                                class="flex items-center gap-1 rounded-lg bg-zinc-800 px-3 py-1 text-sm text-zinc-300 hover:bg-zinc-700"
                            >
                                <Plus class="size-4" />
                                Add Thread
                            </button>
                        </div>
                        <div
                            v-if="form.thread_parts.length > 0"
                            class="space-y-3"
                        >
                            <div
                                v-for="(part, index) in form.thread_parts"
                                :key="index"
                                class="flex gap-2"
                            >
                                <div class="flex-1">
                                    <textarea
                                        v-model="form.thread_parts[index]"
                                        rows="3"
                                        :maxlength="maxTweetLength"
                                        :placeholder="`Thread part ${index + 1}`"
                                        class="w-full rounded-lg border-zinc-700 bg-zinc-900 px-4 py-2 text-white placeholder-zinc-500 focus:border-red-500 focus:ring-red-500"
                                    />
                                    <div class="mt-1 text-xs text-zinc-500">
                                        {{ threadCharacterCounts[index] }}
                                        remaining
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="removeThreadPart(index)"
                                    class="mt-1 text-red-500 hover:text-red-400"
                                >
                                    <Trash2 class="size-5" />
                                </button>
                            </div>
                        </div>
                        <div
                            v-if="form.errors.thread_parts"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ form.errors.thread_parts }}
                        </div>
                    </div>

                    <!-- Media URLs -->
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <label
                                class="block text-sm font-medium text-zinc-300"
                            >
                                Media URLs
                                <span class="text-zinc-500"
                                    >({{ form.media_urls.length }}/4)</span
                                >
                            </label>
                            <button
                                v-if="form.media_urls.length < 4"
                                type="button"
                                @click="addMediaUrl"
                                class="flex items-center gap-1 rounded-lg bg-zinc-800 px-3 py-1 text-sm text-zinc-300 hover:bg-zinc-700"
                            >
                                <Plus class="size-4" />
                                Add Media
                            </button>
                        </div>
                        <div
                            v-if="form.media_urls.length > 0"
                            class="space-y-2"
                        >
                            <div
                                v-for="(url, index) in form.media_urls"
                                :key="index"
                                class="flex gap-2"
                            >
                                <input
                                    v-model="form.media_urls[index]"
                                    type="url"
                                    :placeholder="`Media URL ${index + 1}`"
                                    class="flex-1 rounded-lg border-zinc-700 bg-zinc-900 px-4 py-2 text-white placeholder-zinc-500 focus:border-red-500 focus:ring-red-500"
                                />
                                <button
                                    type="button"
                                    @click="removeMediaUrl(index)"
                                    class="text-red-500 hover:text-red-400"
                                >
                                    <X class="size-5" />
                                </button>
                            </div>
                        </div>
                        <div
                            v-if="form.errors.media_urls"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ form.errors.media_urls }}
                        </div>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-300">
                            Status
                        </label>
                        <div class="mt-2 flex gap-4">
                            <label
                                class="flex cursor-pointer items-center gap-2"
                            >
                                <input
                                    v-model="form.status"
                                    type="radio"
                                    value="draft"
                                    class="text-red-600 focus:ring-red-500"
                                />
                                <span class="text-sm text-zinc-300">Draft</span>
                            </label>
                            <label
                                class="flex cursor-pointer items-center gap-2"
                            >
                                <input
                                    v-model="form.status"
                                    type="radio"
                                    value="scheduled"
                                    class="text-red-600 focus:ring-red-500"
                                />
                                <span class="text-sm text-zinc-300"
                                    >Scheduled</span
                                >
                            </label>
                            <label
                                class="flex cursor-pointer items-center gap-2"
                            >
                                <input
                                    v-model="form.status"
                                    type="radio"
                                    value="publish_immediately"
                                    class="text-red-600 focus:ring-red-500"
                                />
                                <span class="text-sm text-zinc-300"
                                    >Publish Immediately</span
                                >
                            </label>
                        </div>
                    </div>

                    <!-- Scheduled For -->
                    <div>
                        <label
                            for="scheduled_for"
                            class="block text-sm font-medium text-zinc-300"
                        >
                            Schedule For
                            <span v-if="isScheduled" class="text-red-500"
                                >*</span
                            >
                        </label>
                        <div class="mt-1">
                            <DateTimePicker
                                id="scheduled_for"
                                v-model="form.scheduled_for"
                                :required="isScheduled"
                            />
                        </div>
                        <div
                            v-if="form.errors.scheduled_for"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ form.errors.scheduled_for }}
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-4">
                        <Link
                            href="/x-posts"
                            class="rounded-lg bg-zinc-800 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50"
                        >
                            {{
                                form.processing
                                    ? 'Creating...'
                                    : isPublishImmediately
                                      ? 'Publish Now'
                                      : isScheduled
                                        ? 'Schedule Post'
                                        : 'Create Draft'
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppSidebarLayout>
</template>
