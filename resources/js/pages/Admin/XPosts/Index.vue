<script setup lang="ts">
import type { PaginatedXPosts, XPost } from '@/types/models';

import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { computed, ref, watch } from 'vue';

import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';

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

function getStatusBadgeClass(status: string): string {
    switch (status) {
        case 'draft':
            return 'bg-yellow-900/50 text-yellow-300';
        case 'scheduled':
            return 'bg-blue-900/50 text-blue-300';
        case 'published':
            return 'bg-green-900/50 text-green-300';
        case 'failed':
            return 'bg-red-900/50 text-red-300';
        case 'cancelled':
            return 'bg-zinc-800 text-zinc-400';
        default:
            return 'bg-zinc-800 text-zinc-400';
    }
}

function formatStatus(status: string): string {
    return status.charAt(0).toUpperCase() + status.slice(1);
}

function truncateContent(content: string | null, maxLength = 60): string {
    if (!content) {
        return '(No content)';
    }
    if (content.length <= maxLength) {
        return content;
    }
    return content.slice(0, maxLength) + '...';
}

function formatDate(dateString: string | null): string {
    if (!dateString) {
        return '-';
    }
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
        router.post(`/x-posts/${xPost.id}/publish`, {}, { preserveScroll: true });
    }
}

function cancelPost(xPost: XPost) {
    if (confirm('Are you sure you want to cancel this scheduled post?')) {
        router.post(`/x-posts/${xPost.id}/cancel`, {}, { preserveScroll: true });
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
</script>

<template>
    <AppSidebarLayout>
        <Head title="Manage X Posts - Admin" />

        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white">Manage X Posts</h1>
                    <p class="mt-2 text-zinc-400">
                        {{ xPosts?.total ?? 0 }} total posts
                    </p>
                </div>
                <Link
                    href="/x-posts/create"
                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                >
                    Create New Post
                </Link>
            </div>

            <!-- Filters -->
            <div class="mb-6 flex flex-col gap-4 sm:flex-row">
                <!-- Search -->
                <div class="flex-1">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search posts..."
                        class="w-full rounded-lg border-zinc-700 bg-zinc-900 px-4 py-2 text-white placeholder-zinc-500 focus:border-red-500 focus:ring-red-500"
                    />
                </div>

                <!-- Status Filter -->
                <div class="flex gap-2">
                    <button
                        @click="filterByStatus('')"
                        :class="[
                            'rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                            !statusFilter
                                ? 'bg-red-600 text-white'
                                : 'bg-zinc-800 text-zinc-300 hover:bg-zinc-700',
                        ]"
                    >
                        All
                    </button>
                    <button
                        v-for="(value, key) in statuses"
                        :key="key"
                        @click="filterByStatus(value)"
                        :class="[
                            'rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                            statusFilter === value
                                ? 'bg-red-600 text-white'
                                : 'bg-zinc-800 text-zinc-300 hover:bg-zinc-700',
                        ]"
                    >
                        {{ formatStatus(value) }}
                    </button>
                </div>
            </div>

            <!-- Posts Table -->
            <div class="overflow-hidden rounded-lg bg-zinc-900">
                <table class="min-w-full divide-y divide-zinc-800">
                    <thead class="bg-zinc-950">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-zinc-400 uppercase"
                            >
                                Content
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-zinc-400 uppercase"
                            >
                                Status
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-zinc-400 uppercase"
                            >
                                Date
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-zinc-400 uppercase"
                            >
                                Author
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium tracking-wider text-zinc-400 uppercase"
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
                                class="px-6 py-12 text-center text-zinc-400"
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
                                        class="mt-1 text-xs text-zinc-500"
                                    >
                                        + {{ xPost.thread_parts.length }} more
                                        tweets in thread
                                    </div>
                                    <div
                                        v-if="xPost.media_urls?.length"
                                        class="mt-1 text-xs text-zinc-500"
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
                                    <span class="text-zinc-500">Scheduled:</span>
                                    {{ formatDate(xPost.scheduled_for) }}
                                </div>
                                <div v-else-if="xPost.status === 'published'">
                                    <span class="text-zinc-500">Published:</span>
                                    {{ formatDate(xPost.published_at) }}
                                </div>
                                <div v-else>
                                    <span class="text-zinc-500">Created:</span>
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
                                    <!-- Draft actions -->
                                    <template v-if="xPost.status === 'draft'">
                                        <Link
                                            :href="`/x-posts/${xPost.id}/edit`"
                                            class="text-red-500 hover:text-red-400"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            @click="publishPost(xPost)"
                                            class="text-green-500 hover:text-green-400"
                                        >
                                            Publish
                                        </button>
                                        <button
                                            @click="deletePost(xPost)"
                                            class="text-zinc-400 hover:text-white"
                                        >
                                            Delete
                                        </button>
                                    </template>

                                    <!-- Scheduled actions -->
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

                                    <!-- Published actions -->
                                    <template
                                        v-else-if="xPost.status === 'published'"
                                    >
                                        <a
                                            v-if="xPost.x_post_id"
                                            :href="getXPostUrl(xPost.x_post_id)"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="text-blue-500 hover:text-blue-400"
                                        >
                                            View on X
                                        </a>
                                    </template>

                                    <!-- Failed actions -->
                                    <template
                                        v-else-if="xPost.status === 'failed'"
                                    >
                                        <Link
                                            :href="`/x-posts/${xPost.id}/edit`"
                                            class="text-red-500 hover:text-red-400"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            @click="publishPost(xPost)"
                                            class="text-green-500 hover:text-green-400"
                                        >
                                            Retry
                                        </button>
                                        <button
                                            @click="deletePost(xPost)"
                                            class="text-zinc-400 hover:text-white"
                                        >
                                            Delete
                                        </button>
                                    </template>

                                    <!-- Cancelled actions -->
                                    <template
                                        v-else-if="xPost.status === 'cancelled'"
                                    >
                                        <button
                                            @click="deletePost(xPost)"
                                            class="text-zinc-400 hover:text-white"
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
                            ? 'bg-red-600 text-white'
                            : link.url
                              ? 'bg-zinc-800 text-zinc-300 hover:bg-zinc-700'
                              : 'cursor-not-allowed bg-zinc-900 text-zinc-600',
                    ]"
                >
                    <!-- eslint-disable-next-line vue/no-v-html -->
                    <span v-html="link.label" />
                </Link>
            </div>
        </div>
    </AppSidebarLayout>
</template>
