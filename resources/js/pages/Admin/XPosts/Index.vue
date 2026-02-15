<script setup lang="ts">
import type { PaginatedXPosts, XPost } from '../../../types/x-suite';

import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { computed, ref, watch } from 'vue';

import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';

import XPostPreviewCard from '../../../components/x-suite/XPostPreviewCard.vue';
import { XPostStatus } from '../../../types/x-suite';

interface Props {
    xPosts: PaginatedXPosts;
    queryParams: {
        search?: string;
        status?: string;
    };
    statuses: Record<string, string>;
}

const props = defineProps<Props>();

const queryParams = computed(() => props.queryParams);
const search = ref(queryParams.value?.search || '');
const statusFilter = ref(queryParams.value?.status || '');

const debouncedSearch = useDebounceFn((searchValue: string) => {
    router.get(
        '/x-posts',
        {
            search: searchValue || undefined,
            status: statusFilter.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
}, 300);

watch(search, (value) => {
    void debouncedSearch(value);
});

function filterByStatus(status: string) {
    statusFilter.value = status;
    router.get(
        '/x-posts',
        {
            search: search.value || undefined,
            status: status || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
}

function getStatusBadgeClass(status: XPostStatus | string): string {
    switch (status) {
        case XPostStatus.Draft:
            return 'bg-yellow-900/50 text-yellow-300';
        case XPostStatus.Scheduled:
            return 'bg-blue-900/50 text-blue-300';
        case XPostStatus.Published:
            return 'bg-green-900/50 text-green-300';
        case XPostStatus.Failed:
            return 'bg-red-900/50 text-red-300';
        case XPostStatus.Cancelled:
            return 'bg-zinc-800 text-zinc-400';
        default:
            return 'bg-zinc-800 text-zinc-400';
    }
}

function formatStatus(status: string): string {
    return status.charAt(0).toUpperCase() + status.slice(1);
}

function truncateContent(content: string | null, maxLength = 60): string {
    if (!content) return '(No content)';
    if (content.length <= maxLength) return content;
    return content.slice(0, maxLength) + '...';
}

function formatDate(dateString: string | null): string {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

function publishPost(xPost: XPost) {
    if (confirm('Are you sure you want to publish this post now?')) {
        router.post(
            `/x-posts/${xPost.id}/publish`,
            {},
            { preserveScroll: true },
        );
    }
}

function cancelPost(xPost: XPost) {
    if (confirm('Are you sure you want to cancel this scheduled post?')) {
        router.post(
            `/x-posts/${xPost.id}/cancel`,
            {},
            { preserveScroll: true },
        );
    }
}

function deletePost(xPost: XPost) {
    if (confirm('Are you sure you want to delete this post?')) {
        router.delete(`/x-posts/${xPost.id}`, { preserveScroll: true });
    }
}

function getXPostUrl(xPostId: string): string {
    return `https://x.com/i/web/status/${xPostId}`;
}

const previewPost = ref<XPost | null>(null);

function openPreview(xPost: XPost) {
    previewPost.value = xPost;
}

function closePreview() {
    previewPost.value = null;
}
</script>

<template>
    <AppSidebarLayout>
        <Head title="Manage X Posts" />

        <div class="w-full px-4 py-12 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white">
                        Manage X Posts
                    </h1>
                    <p class="mt-2 text-zinc-300">
                        {{ xPosts?.total ?? 0 }} total posts
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        href="/x-posts/import"
                        class="rounded-lg border border-zinc-600 bg-zinc-800 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700"
                    >
                        Import from Spreadsheet
                    </Link>
                    <Link
                        href="/x-posts/create"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                    >
                        Create New Post
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <div class="mb-6 flex flex-col gap-4 sm:flex-row">
                <div class="flex-1">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search posts..."
                        class="w-full rounded-lg border-zinc-700 bg-zinc-900 px-4 py-2 text-white placeholder-zinc-500 focus:border-blue-500 focus:ring-blue-500"
                    />
                </div>
                <div class="flex gap-2">
                    <button
                        @click="filterByStatus('')"
                        :class="[
                            'rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                            !statusFilter
                                ? 'bg-blue-600 text-white'
                                : 'bg-zinc-800 text-zinc-300 hover:bg-zinc-700',
                        ]"
                    >
                        All
                    </button>
                    <button
                        v-for="(label, status) in statuses"
                        :key="status"
                        @click="filterByStatus(status)"
                        :class="[
                            'rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                            statusFilter === status
                                ? 'bg-blue-600 text-white'
                                : 'bg-zinc-800 text-zinc-300 hover:bg-zinc-700',
                        ]"
                    >
                        {{ label }}
                    </button>
                </div>
            </div>

            <!-- Posts Table -->
            <div class="overflow-hidden rounded-lg bg-zinc-900">
                <table class="min-w-full divide-y divide-zinc-800">
                    <thead class="bg-zinc-950">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-zinc-300 uppercase"
                            >
                                Content
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-zinc-300 uppercase"
                            >
                                Status
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-zinc-300 uppercase"
                            >
                                Date
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-zinc-300 uppercase"
                            >
                                Author
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium tracking-wider text-zinc-300 uppercase"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-800">
                        <tr
                            v-if="!xPosts?.data?.length"
                            class="hover:bg-zinc-800/50"
                        >
                            <td
                                colspan="5"
                                class="px-6 py-12 text-center text-zinc-300"
                            >
                                No posts found.
                            </td>
                        </tr>
                        <tr
                            v-for="xPost in xPosts?.data"
                            :key="xPost.id"
                            class="hover:bg-zinc-800/50"
                        >
                            <td class="px-6 py-4">
                                <div class="max-w-xs">
                                    <div class="font-medium text-white">
                                        {{ truncateContent(xPost.content) }}
                                    </div>
                                    <div
                                        v-if="xPost.thread_parts?.length"
                                        class="mt-1 text-xs text-zinc-400"
                                    >
                                        + {{ xPost.thread_parts.length }} more
                                        tweets in thread
                                    </div>
                                    <div
                                        v-if="xPost.media_urls?.length"
                                        class="mt-1 text-xs text-zinc-400"
                                    >
                                        {{ xPost.media_urls.length }} media
                                        attached
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    :class="[
                                        'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                        getStatusBadgeClass(xPost.status),
                                    ]"
                                >
                                    {{ formatStatus(xPost.status) }}
                                </span>
                                <div
                                    v-if="
                                        xPost.status === 'failed' &&
                                        xPost.error_message
                                    "
                                    class="mt-1 max-w-xs truncate text-xs text-red-400"
                                    :title="xPost.error_message"
                                >
                                    {{ xPost.error_message }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-zinc-300">
                                <div v-if="xPost.status === 'scheduled'">
                                    <span class="text-zinc-400"
                                        >Scheduled:</span
                                    >
                                    {{ formatDate(xPost.scheduled_for) }}
                                </div>
                                <div v-else-if="xPost.status === 'published'">
                                    <span class="text-zinc-400"
                                        >Published:</span
                                    >
                                    {{ formatDate(xPost.published_at) }}
                                </div>
                                <div v-else>
                                    <span class="text-zinc-400">Created:</span>
                                    {{ formatDate(xPost.created_at) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-zinc-300">
                                {{ xPost.user?.name ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <button
                                        type="button"
                                        class="text-zinc-400 hover:text-white"
                                        @click="openPreview(xPost)"
                                    >
                                        Preview
                                    </button>
                                    <template v-if="xPost.status === 'draft'">
                                        <Link
                                            :href="`/x-posts/${xPost.id}/edit`"
                                            class="text-blue-500 hover:text-blue-400"
                                            >Edit</Link
                                        >
                                        <button
                                            @click="publishPost(xPost)"
                                            class="text-green-500 hover:text-green-400"
                                        >
                                            Publish
                                        </button>
                                        <button
                                            @click="deletePost(xPost)"
                                            class="text-zinc-300 hover:text-white"
                                        >
                                            Delete
                                        </button>
                                    </template>
                                    <template
                                        v-else-if="xPost.status === 'scheduled'"
                                    >
                                        <button
                                            @click="publishPost(xPost)"
                                            class="text-green-500 hover:text-green-400"
                                        >
                                            Publish Now
                                        </button>
                                        <button
                                            @click="cancelPost(xPost)"
                                            class="text-yellow-500 hover:text-yellow-400"
                                        >
                                            Cancel
                                        </button>
                                    </template>
                                    <template
                                        v-else-if="xPost.status === 'published'"
                                    >
                                        <a
                                            v-if="xPost.x_post_id"
                                            :href="getXPostUrl(xPost.x_post_id)"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="text-blue-500 hover:text-blue-400"
                                            >View on X</a
                                        >
                                    </template>
                                    <template
                                        v-else-if="xPost.status === 'failed'"
                                    >
                                        <Link
                                            :href="`/x-posts/${xPost.id}/edit`"
                                            class="text-blue-500 hover:text-blue-400"
                                            >Edit</Link
                                        >
                                        <button
                                            @click="publishPost(xPost)"
                                            class="text-green-500 hover:text-green-400"
                                        >
                                            Retry
                                        </button>
                                        <button
                                            @click="deletePost(xPost)"
                                            class="text-zinc-300 hover:text-white"
                                        >
                                            Delete
                                        </button>
                                    </template>
                                    <template
                                        v-else-if="xPost.status === 'cancelled'"
                                    >
                                        <button
                                            @click="deletePost(xPost)"
                                            class="text-zinc-300 hover:text-white"
                                        >
                                            Delete
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="xPosts?.last_page && xPosts.last_page > 1"
                class="mt-6 flex justify-center gap-2"
            >
                <Link
                    v-for="link in xPosts.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    :class="[
                        'rounded px-3 py-2 text-sm',
                        link.active
                            ? 'bg-blue-600 text-white'
                            : link.url
                              ? 'bg-zinc-800 text-zinc-300 hover:bg-zinc-700'
                              : 'cursor-not-allowed bg-zinc-900 text-zinc-600',
                    ]"
                >
                    <!-- eslint-disable-next-line vue/no-v-html -->
                    <span v-html="link.label" />
                </Link>
            </div>

            <!-- Preview overlay -->
            <div
                v-if="previewPost"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                @click.self="closePreview"
            >
                <div class="mx-4 w-full max-w-md">
                    <XPostPreviewCard
                        :content="previewPost.content || ''"
                        :media-urls="previewPost.media_urls || []"
                    />
                    <button
                        @click="closePreview"
                        class="mt-4 w-full rounded-lg bg-zinc-800 px-4 py-2 text-sm text-white hover:bg-zinc-700"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </AppSidebarLayout>
</template>
