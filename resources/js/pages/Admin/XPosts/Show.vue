<script setup lang="ts">
import type { XPost } from '../../../types/x-suite';

import { Head, Link } from '@inertiajs/vue3';

import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';

import XPostPreviewCard from '../../../components/x-suite/XPostPreviewCard.vue';

interface Props {
    xPost: XPost;
}

defineProps<Props>();

function formatDate(dateString: string | null): string {
    if (!dateString) {
        return '-';
    }
    return new Date(dateString).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

function getXPostUrl(tweetId: string): string {
    return `https://x.com/i/web/status/${tweetId}`;
}
</script>

<template>
    <AppSidebarLayout>
        <Head title="X Post Details" />

        <div class="w-full px-4 py-12 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-3xl">
                <div class="mb-8">
                    <Link
                        href="/x-posts"
                        class="mb-4 inline-block text-sm text-blue-500 hover:text-blue-400"
                    >
                        &larr; Back to Posts
                    </Link>
                    <h1 class="text-3xl font-bold text-white">
                        X Post Details
                    </h1>
                </div>

                <div class="space-y-6 rounded-lg bg-zinc-900 p-6">
                    <!-- Status Badge -->
                    <div>
                        <span
                            :class="[
                                'inline-flex rounded-full px-3 py-1 text-sm font-semibold',
                                xPost.status === 'draft'
                                    ? 'bg-yellow-900/50 text-yellow-300'
                                    : xPost.status === 'scheduled'
                                      ? 'bg-blue-900/50 text-blue-300'
                                      : xPost.status === 'published'
                                        ? 'bg-green-900/50 text-green-300'
                                        : xPost.status === 'failed'
                                          ? 'bg-red-900/50 text-red-300'
                                          : 'bg-zinc-800 text-zinc-400',
                            ]"
                        >
                            {{
                                xPost.status.charAt(0).toUpperCase() +
                                xPost.status.slice(1)
                            }}
                        </span>
                    </div>

                    <!-- Preview -->
                    <div>
                        <h3 class="mb-2 text-sm font-medium text-zinc-400">
                            How it looks on X
                        </h3>
                        <XPostPreviewCard
                            :content="xPost.content || ''"
                            :media-urls="xPost.media_urls || []"
                        />
                    </div>

                    <!-- Content -->
                    <div>
                        <h3 class="mb-2 text-sm font-medium text-zinc-400">
                            Content
                        </h3>
                        <p class="whitespace-pre-wrap text-white">
                            {{ xPost.content || '(No content)' }}
                        </p>
                    </div>

                    <!-- Thread Parts -->
                    <div
                        v-if="
                            xPost.thread_parts && xPost.thread_parts.length > 0
                        "
                    >
                        <h3 class="mb-2 text-sm font-medium text-zinc-400">
                            Thread Parts ({{ xPost.thread_parts.length }})
                        </h3>
                        <div class="space-y-3">
                            <div
                                v-for="(part, index) in xPost.thread_parts"
                                :key="index"
                                class="rounded-lg bg-zinc-800 p-4"
                            >
                                <div class="mb-1 text-xs text-zinc-500">
                                    Part {{ index + 1 }}
                                </div>
                                <p class="whitespace-pre-wrap text-white">
                                    {{ part }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Media URLs -->
                    <div v-if="xPost.media_urls && xPost.media_urls.length > 0">
                        <h3 class="mb-2 text-sm font-medium text-zinc-400">
                            Media ({{ xPost.media_urls.length }})
                        </h3>
                        <div class="space-y-2">
                            <a
                                v-for="(url, index) in xPost.media_urls"
                                :key="index"
                                :href="url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="block truncate text-sm text-blue-500 hover:text-blue-400"
                            >
                                {{ url }}
                            </a>
                        </div>
                    </div>

                    <!-- Dates -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div v-if="xPost.scheduled_for">
                            <h3 class="mb-1 text-sm font-medium text-zinc-400">
                                Scheduled For
                            </h3>
                            <p class="text-white">
                                {{ formatDate(xPost.scheduled_for) }}
                            </p>
                        </div>
                        <div v-if="xPost.published_at">
                            <h3 class="mb-1 text-sm font-medium text-zinc-400">
                                Published At
                            </h3>
                            <p class="text-white">
                                {{ formatDate(xPost.published_at) }}
                            </p>
                        </div>
                        <div>
                            <h3 class="mb-1 text-sm font-medium text-zinc-400">
                                Created At
                            </h3>
                            <p class="text-white">
                                {{ formatDate(xPost.created_at) }}
                            </p>
                        </div>
                        <div>
                            <h3 class="mb-1 text-sm font-medium text-zinc-400">
                                Updated At
                            </h3>
                            <p class="text-white">
                                {{ formatDate(xPost.updated_at) }}
                            </p>
                        </div>
                    </div>

                    <!-- X Post Link -->
                    <div v-if="xPost.x_post_id">
                        <h3 class="mb-2 text-sm font-medium text-zinc-400">
                            X Post ID
                        </h3>
                        <a
                            :href="getXPostUrl(xPost.x_post_id)"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-blue-500 hover:text-blue-400"
                        >
                            View on X &rarr;
                        </a>
                    </div>

                    <!-- Error Message -->
                    <div v-if="xPost.error_message">
                        <h3 class="mb-2 text-sm font-medium text-red-400">
                            Error Message
                        </h3>
                        <p
                            class="rounded-lg bg-red-900/50 p-4 text-sm text-red-300"
                        >
                            {{ xPost.error_message }}
                        </p>
                    </div>

                    <!-- Author -->
                    <div v-if="xPost.user">
                        <h3 class="mb-1 text-sm font-medium text-zinc-400">
                            Author
                        </h3>
                        <p class="text-white">{{ xPost.user.name }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AppSidebarLayout>
</template>
