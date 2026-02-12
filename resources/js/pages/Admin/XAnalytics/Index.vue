<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';

import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';

interface Props {
    performanceReport: {
        total_impressions: number;
        total_likes: number;
        total_retweets: number;
        total_replies: number;
        avg_engagement_rate: number;
        total_posts: number;
    };
    topPerformers: any[];
    queryParams?: {
        start_date?: string;
        end_date?: string;
    };
}

defineProps<Props>();

function sync() {
    if (confirm('Sync analytics for all published posts?')) {
        router.post('/x-analytics/sync', {}, { preserveScroll: true });
    }
}
</script>

<template>
    <AppSidebarLayout>
        <Head title="X Analytics" />

        <div class="w-full px-4 py-12 sm:px-6 lg:px-8">
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white">X Analytics</h1>
                    <p class="mt-2 text-zinc-400">
                        Performance metrics for X posts
                    </p>
                </div>
                <button
                    @click="sync"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                >
                    Sync Analytics
                </button>
            </div>

            <!-- Performance Report -->
            <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg bg-zinc-900 p-6">
                    <div class="text-sm text-zinc-400">Total Impressions</div>
                    <div class="mt-2 text-2xl font-bold text-white">
                        {{
                            performanceReport.total_impressions.toLocaleString()
                        }}
                    </div>
                </div>
                <div class="rounded-lg bg-zinc-900 p-6">
                    <div class="text-sm text-zinc-400">Total Likes</div>
                    <div class="mt-2 text-2xl font-bold text-white">
                        {{ performanceReport.total_likes.toLocaleString() }}
                    </div>
                </div>
                <div class="rounded-lg bg-zinc-900 p-6">
                    <div class="text-sm text-zinc-400">Total Retweets</div>
                    <div class="mt-2 text-2xl font-bold text-white">
                        {{ performanceReport.total_retweets.toLocaleString() }}
                    </div>
                </div>
                <div class="rounded-lg bg-zinc-900 p-6">
                    <div class="text-sm text-zinc-400">Avg Engagement Rate</div>
                    <div class="mt-2 text-2xl font-bold text-white">
                        {{ performanceReport.avg_engagement_rate }}%
                    </div>
                </div>
            </div>

            <!-- Top Performers -->
            <div class="rounded-lg bg-zinc-900 p-6">
                <h2 class="mb-4 text-xl font-bold text-white">
                    Top Performing Posts
                </h2>
                <div
                    v-if="topPerformers.length === 0"
                    class="text-center text-zinc-400"
                >
                    No analytics data available yet.
                </div>
                <div v-else class="space-y-4">
                    <div
                        v-for="(analytics, index) in topPerformers"
                        :key="analytics.id"
                        class="rounded-lg bg-zinc-800 p-4"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-medium text-white">
                                    #{{ index + 1 }} - Tweet ID:
                                    {{ analytics.tweet_id }}
                                </div>
                                <div class="mt-1 text-sm text-zinc-400">
                                    Impressions:
                                    {{ analytics.impressions.toLocaleString() }}
                                    | Likes: {{ analytics.likes }} | Retweets:
                                    {{ analytics.retweets }}
                                </div>
                            </div>
                            <Link
                                v-if="analytics.x_post"
                                :href="`/x-analytics/${analytics.x_post.id}`"
                                class="text-blue-500 hover:text-blue-400"
                            >
                                View Details
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppSidebarLayout>
</template>
