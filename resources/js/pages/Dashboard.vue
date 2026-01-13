<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import StatsGrid from '@/components/StatsGrid.vue';
import TmdbImportsSection from '@/components/TmdbImportsSection.vue';
import QuickActionsSection from '@/components/QuickActionsSection.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import type { Movie, PaginatedMovies } from '@/types/models';

interface Props {
    stats: {
        movies: number;
        tags: number;
        activeFeatures: number;
    };
    tmdbDrafts: PaginatedMovies | [];
    queryParams: {
        search?: string;
    };
}

const props = defineProps<Props>();
const page = usePage();
const auth = page.props.auth as { user: any };

const search = ref(props.queryParams.search || '');
const confirmDialogOpen = ref(false);
const pendingAction = ref<{ type: 'publish' | 'archive'; movie: Movie } | null>(null);

function handleSearchUpdate(value: string) {
    search.value = value;
    router.get('/dashboard', {
        search: value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}

function publishMovie(movie: Movie) {
    pendingAction.value = { type: 'publish', movie };
    confirmDialogOpen.value = true;
}

function archiveMovie(movie: Movie) {
    pendingAction.value = { type: 'archive', movie };
    confirmDialogOpen.value = true;
}

function handleConfirm() {
    if (!pendingAction.value) {
        return;
    }

    const { type, movie } = pendingAction.value;
    const action = type === 'publish' ? 'publish' : 'archive';

    router.post(`/movies/${movie.id}/${action}`, {}, {
        preserveScroll: true,
    });
}

function handleCancel() {
    pendingAction.value = null;
}

const stats = [
    { title: 'Published Movies', value: props.stats.movies },
    { title: 'Total Tags', value: props.stats.tags },
    { title: 'Active Features', value: props.stats.activeFeatures },
];
</script>

<template>
    <Head title="Dashboard - Movies of War" />

    <AppSidebarLayout>
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <!-- Stats Grid -->
            <StatsGrid :stats="stats" class="mb-8" />

            <!-- Admin TMDB Section -->
            <TmdbImportsSection
                v-if="auth.user?.is_admin && Array.isArray(tmdbDrafts.data)"
                :tmdb-drafts="tmdbDrafts"
                :search="search"
                @update:search="handleSearchUpdate"
                @publish="publishMovie"
                @archive="archiveMovie"
            />

            <!-- Quick Actions for Regular Users -->
            <QuickActionsSection v-if="!auth.user?.is_admin" />
        </div>

        <!-- Confirm Dialog -->
        <ConfirmDialog
            v-if="pendingAction"
            v-model:open="confirmDialogOpen"
            :title="`${pendingAction.type === 'publish' ? 'Publish' : 'Archive'} Movie?`"
            :description="`Are you sure you want to ${pendingAction.type === 'publish' ? 'publish' : 'archive'} '${pendingAction.movie.title}'?`"
            :confirm-text="pendingAction.type === 'publish' ? 'Publish' : 'Archive'"
            :variant="pendingAction.type === 'archive' ? 'destructive' : 'default'"
            @confirm="handleConfirm"
            @cancel="handleCancel"
        />
    </AppSidebarLayout>
</template>
