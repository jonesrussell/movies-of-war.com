<script setup lang="ts">
import type {
    PaginatedXTrendKeywords,
    XTrendKeyword,
    XTrendResult,
} from '../../../types/x-suite';

import { Head, router } from '@inertiajs/vue3';

import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';

interface Props {
    keywords: PaginatedXTrendKeywords;
    recentTrends: XTrendResult[];
}

defineProps<Props>();

function searchKeyword(keyword: XTrendKeyword) {
    if (confirm(`Search for keyword: ${keyword.keyword}?`)) {
        router.post(
            `/x-trends/${keyword.id}/search`,
            {},
            { preserveScroll: true },
        );
    }
}

function toggleActive(keyword: XTrendKeyword) {
    router.put(
        `/x-trends/${keyword.id}`,
        { is_active: !keyword.is_active },
        { preserveScroll: true },
    );
}

function deleteKeyword(keyword: XTrendKeyword) {
    if (confirm(`Delete keyword: ${keyword.keyword}?`)) {
        router.delete(`/x-trends/${keyword.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <AppSidebarLayout>
        <Head title="Trend Monitoring" />

        <div class="w-full px-4 py-12 sm:px-6 lg:px-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-white">Trend Monitoring</h1>
                <p class="mt-2 text-zinc-400">
                    Monitor keywords and hashtags on X
                </p>
            </div>

            <!-- Keywords Table -->
            <div class="mb-8 rounded-lg bg-zinc-900 p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-xl font-bold text-white">
                        Monitored Keywords
                    </h2>
                    <button
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                    >
                        Add Keyword
                    </button>
                </div>
                <div
                    v-if="keywords.data.length === 0"
                    class="text-center text-zinc-400"
                >
                    No keywords being monitored yet.
                </div>
                <div v-else class="space-y-2">
                    <div
                        v-for="keyword in keywords.data"
                        :key="keyword.id"
                        class="flex items-center justify-between rounded-lg bg-zinc-800 p-4"
                    >
                        <div>
                            <div class="font-medium text-white">
                                {{ keyword.keyword }}
                            </div>
                            <div class="mt-1 text-sm text-zinc-400">
                                Type: {{ keyword.type }} | Results:
                                {{ keyword.results_count ?? 0 }}
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button
                                @click="searchKeyword(keyword)"
                                class="text-blue-500 hover:text-blue-400"
                            >
                                Search
                            </button>
                            <button
                                @click="toggleActive(keyword)"
                                :class="
                                    keyword.is_active
                                        ? 'text-green-500'
                                        : 'text-zinc-500'
                                "
                            >
                                {{ keyword.is_active ? 'Active' : 'Inactive' }}
                            </button>
                            <button
                                @click="deleteKeyword(keyword)"
                                class="text-red-500 hover:text-red-400"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Trends -->
            <div class="rounded-lg bg-zinc-900 p-6">
                <h2 class="mb-4 text-xl font-bold text-white">Recent Trends</h2>
                <div
                    v-if="recentTrends.length === 0"
                    class="text-center text-zinc-400"
                >
                    No recent trends found.
                </div>
                <div v-else class="space-y-4">
                    <div
                        v-for="trend in recentTrends"
                        :key="trend.id"
                        class="rounded-lg bg-zinc-800 p-4"
                    >
                        <div class="text-white">{{ trend.content }}</div>
                        <div class="mt-2 text-sm text-zinc-400">
                            @{{ trend.author_username }} | Likes:
                            {{ trend.like_count }} | Retweets:
                            {{ trend.retweet_count }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppSidebarLayout>
</template>
