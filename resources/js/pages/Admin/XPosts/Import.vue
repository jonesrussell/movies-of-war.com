<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import XPostPreviewCard from '../../../components/x-suite/XPostPreviewCard.vue';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';

interface PreviewRow {
    row_number: number;
    content: string;
    content_preview: string;
    media_urls: string[];
    scheduled_for: string | null;
    theme: string | null;
    post_type: string | null;
}

interface Props {
    preview: PreviewRow[];
    maxTweetLength: number;
}

const props = defineProps<Props>();

const selected = ref<Set<number>>(new Set());

const form = useForm({
    row_numbers: [] as number[],
});

const allSelected = computed({
    get() {
        return (
            props.preview.length > 0 &&
            selected.value.size === props.preview.length
        );
    },
    set(checked: boolean) {
        if (checked) {
            selected.value = new Set(props.preview.map((r) => r.row_number));
        } else {
            selected.value = new Set();
        }
    },
});

function toggleRow(rowNumber: number) {
    const next = new Set(selected.value);
    if (next.has(rowNumber)) {
        next.delete(rowNumber);
    } else {
        next.add(rowNumber);
    }
    selected.value = next;
}

function submit() {
    form.row_numbers = Array.from(selected.value);
    form.post('/x-posts/import', {
        onSuccess: () => {
            router.visit('/x-posts');
        },
    });
}

const canSubmit = computed(() => selected.value.size > 0);
const selectedCount = computed(() => selected.value.size);
</script>

<template>
    <AppSidebarLayout>
        <Head title="Import X Posts from Spreadsheet" />

        <div class="w-full px-4 py-12 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-5xl">
                <div class="mb-8">
                    <Link
                        href="/x-posts"
                        class="mb-4 inline-block text-sm text-blue-500 hover:text-blue-400"
                    >
                        &larr; Back to Posts
                    </Link>
                    <h1 class="text-3xl font-bold text-white">
                        Import from Spreadsheet
                    </h1>
                    <p class="mt-2 text-zinc-400">
                        Select rows to import as draft X posts. You can edit and schedule
                        them after importing.
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="flex items-center justify-between gap-4">
                        <label
                            class="flex cursor-pointer items-center gap-2 text-sm text-zinc-300"
                        >
                            <input
                                v-model="allSelected"
                                type="checkbox"
                                class="rounded border-zinc-600 text-blue-600 focus:ring-blue-500"
                            />
                            Select all ({{ preview.length }} rows)
                        </label>
                        <div class="text-sm text-zinc-400">
                            {{ selectedCount }} selected
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div
                            v-for="row in preview"
                            :key="row.row_number"
                            class="flex gap-4 rounded-lg border border-zinc-800 bg-zinc-900/50 p-4"
                        >
                            <div class="flex shrink-0 pt-1">
                                <input
                                    :id="`row-${row.row_number}`"
                                    type="checkbox"
                                    :checked="selected.has(row.row_number)"
                                    class="rounded border-zinc-600 text-blue-600 focus:ring-blue-500"
                                    @change="toggleRow(row.row_number)"
                                />
                            </div>
                            <div class="min-w-0 flex-1 space-y-3">
                                <div
                                    class="flex flex-wrap items-center gap-2 text-xs text-zinc-500"
                                >
                                    <span>Row {{ row.row_number }}</span>
                                    <span
                                        v-if="row.theme"
                                        class="rounded bg-zinc-800 px-2 py-0.5"
                                    >
                                        {{ row.theme }}
                                    </span>
                                    <span
                                        v-if="row.post_type"
                                        class="rounded bg-zinc-800 px-2 py-0.5"
                                    >
                                        {{ row.post_type }}
                                    </span>
                                </div>
                                <div class="text-sm text-zinc-300">
                                    {{ row.content_preview }}
                                </div>
                                <XPostPreviewCard
                                    :content="row.content"
                                    :media-urls="row.media_urls"
                                    class="max-w-md"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-4">
                        <Link
                            href="/x-posts"
                            class="rounded-lg bg-zinc-800 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="!canSubmit || form.processing"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{
                                form.processing
                                    ? 'Importing...'
                                    : `Import ${selectedCount} as drafts`
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppSidebarLayout>
</template>
