<script setup lang="ts">
import type { PaginatedXCuratedPosts } from '../../../types/x-suite';

import { Head, router } from '@inertiajs/vue3';

import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';

interface Props {
    posts: PaginatedXCuratedPosts;
    filters: {
        featured?: boolean;
        high_engagement?: boolean;
        recent?: boolean;
    };
}

defineProps<Props>();

function discover() {
    if (confirm('Run content discovery scan?')) {
        router.post(
            '/x-content-discovery/discover',
            {},
            { preserveScroll: true },
        );
    }
}

function deletePost(post: any) {
    if (confirm('Remove this curated post?')) {
        router.delete(`/x-content-discovery/${post.id}`, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <AppSidebarLayout>
        <Head title="Content Discovery" />

        <div class="w-full px-4 py-12 sm:px-6 lg:px-8">
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white">
                        Content Discovery
                    </h1>
                    <p class="mt-2 text-zinc-400">
                        Curated high-quality content from X
                    </p>
                </div>
                <button
                    @click="discover"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                >
                    Discover Content
                </button>
            </div>

            <div class="rounded-lg bg-zinc-900 p-6">
                <div
                    v-if="posts.data.length === 0"
                    class="text-center text-zinc-400"
                >
                    No curated posts yet. Run a discovery scan to find content.
                </div>
                <div v-else class="space-y-4">
                    <div
                        v-for="post in posts.data"
                        :key="post.id"
                        class="rounded-lg bg-zinc-800 p-4"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="text-white">{{ post.content }}</div>
                                <div class="mt-2 text-sm text-zinc-400">
                                    @{{ post.author_username }} | Likes:
                                    {{ post.like_count }} | Retweets:
                                    {{ post.retweet_count }}
                                </div>
                                <div
                                    v-if="post.notes"
                                    class="mt-2 text-sm text-zinc-300"
                                >
                                    Notes: {{ post.notes }}
                                </div>
                            </div>
                            <div class="ml-4 flex gap-2">
                                <a
                                    :href="`https://x.com/i/web/status/${post.tweet_id}`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-blue-500 hover:text-blue-400"
                                >
                                    View on X
                                </a>
                                <button
                                    @click="deletePost(post)"
                                    class="text-red-500 hover:text-red-400"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppSidebarLayout>
</template>
