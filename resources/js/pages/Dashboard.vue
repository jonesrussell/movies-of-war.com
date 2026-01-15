<script setup lang="ts">
import type { User } from '@/types/models';

import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

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
    };
}

interface PageProps {
    auth: {
        user?: User;
    };
    stats: {
        movies: number;
        tags: number;
        activeFeatures: number;
        tmdbDrafts: number;
    };
}

const props = defineProps<Props>();
const page = usePage<PageProps>();

const auth = page.props.auth;

const statsArray = computed(() => [
    { title: 'Published Movies', value: props.stats.movies },
    { title: 'Total Tags', value: props.stats.tags },
    { title: 'Active Features', value: props.stats.activeFeatures },
]);
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

            <!-- Quick Actions for Regular Users -->
            <QuickActionsSection v-if="!auth.user?.is_admin" />
        </div>
    </AppSidebarLayout>
</template>
