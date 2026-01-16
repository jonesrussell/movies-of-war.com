<script setup lang="ts">
import type { AppPageProps } from '@/types';

import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import { Link } from '@inertiajs/vue3';

import QuickActionsSection from '@/components/QuickActionsSection.vue';
import StatsGrid from '@/components/StatsGrid.vue';
import TmdbImportsSummaryCard from '@/components/TmdbImportsSummaryCard.vue';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';

interface Props {
    stats: {
        movies: number;
        tags: number;
        activeFeatures: number;
        tmdbDrafts: number;
        x_published_posts?: number;
        x_scheduled_posts?: number;
        x_failed_posts?: number;
        x_total_impressions?: number;
        x_active_keywords?: number;
    };
}

interface PageProps extends AppPageProps {
    stats: {
        movies: number;
        tags: number;
        activeFeatures: number;
        tmdbDrafts: number;
        x_published_posts?: number;
        x_scheduled_posts?: number;
        x_failed_posts?: number;
        x_total_impressions?: number;
        x_active_keywords?: number;
    };
}

const props = defineProps<Props>();
const page = usePage<PageProps>();

const auth = page.props.auth;

const statsArray = computed(() => {
    const base = [
        { title: 'Published Movies', value: props.stats.movies },
        { title: 'Total Tags', value: props.stats.tags },
        { title: 'Active Features', value: props.stats.activeFeatures },
    ];

    // Add X API stats for admins
    if (auth.user?.is_admin && props.stats.x_published_posts !== undefined) {
        base.push(
            { title: 'X Published Posts', value: props.stats.x_published_posts },
            { title: 'X Scheduled Posts', value: props.stats.x_scheduled_posts ?? 0 },
            { title: 'X Total Impressions', value: props.stats.x_total_impressions?.toLocaleString() ?? '0' },
        );
    }

    return base;
});
</script>

<template>
    <Head title="Dashboard - Movies of War" />

    <AppSidebarLayout>
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <!-- Stats Grid -->
            <StatsGrid :stats="statsArray" class="mb-8" />

            <!-- Admin TMDB Summary Card -->
            <div v-if="auth.user?.is_admin" class="mb-8">
                <TmdbImportsSummaryCard :draft-count="props.stats.tmdbDrafts" />
            </div>

            <!-- Admin X API Quick Links -->
            <div v-if="auth.user?.is_admin" class="mb-8">
                <div class="rounded-lg bg-zinc-900 p-6">
                    <h2 class="mb-4 text-xl font-bold text-white">X API Management</h2>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Link
                            href="/x-posts"
                            class="rounded-lg bg-zinc-800 p-4 text-center transition-colors hover:bg-zinc-700"
                        >
                            <div class="text-sm text-zinc-400">Manage Posts</div>
                            <div class="mt-1 text-lg font-semibold text-white">
                                {{ stats.x_published_posts ?? 0 }} Published
                            </div>
                        </Link>
                        <Link
                            href="/dashboard/x-analytics"
                            class="rounded-lg bg-zinc-800 p-4 text-center transition-colors hover:bg-zinc-700"
                        >
                            <div class="text-sm text-zinc-400">View Analytics</div>
                            <div class="mt-1 text-lg font-semibold text-white">
                                {{ stats.x_total_impressions?.toLocaleString() ?? '0' }}
                                Impressions
                            </div>
                        </Link>
                        <Link
                            href="/dashboard/x-trends"
                            class="rounded-lg bg-zinc-800 p-4 text-center transition-colors hover:bg-zinc-700"
                        >
                            <div class="text-sm text-zinc-400">Monitor Trends</div>
                            <div class="mt-1 text-lg font-semibold text-white">
                                {{ stats.x_active_keywords ?? 0 }} Keywords
                            </div>
                        </Link>
                        <Link
                            href="/dashboard/x-auto-replies"
                            class="rounded-lg bg-zinc-800 p-4 text-center transition-colors hover:bg-zinc-700"
                        >
                            <div class="text-sm text-zinc-400">Auto Replies</div>
                            <div class="mt-1 text-lg font-semibold text-white">
                                Manage Rules
                            </div>
                        </Link>
                        <Link
                            href="/dashboard/x-content-discovery"
                            class="rounded-lg bg-zinc-800 p-4 text-center transition-colors hover:bg-zinc-700"
                        >
                            <div class="text-sm text-zinc-400">Content Discovery</div>
                            <div class="mt-1 text-lg font-semibold text-white">
                                Curated Feed
                            </div>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Quick Actions for Regular Users -->
            <QuickActionsSection v-if="!auth.user?.is_admin" />
        </div>
    </AppSidebarLayout>
</template>
