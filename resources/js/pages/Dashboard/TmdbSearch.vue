<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { CheckCircle, Download, Search } from 'lucide-vue-next';
import { computed, ref, toRef } from 'vue';

import TmdbSearchPreviewDialog from '@/components/TmdbSearchPreviewDialog.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import UpcomingBadge from '@/components/UpcomingBadge.vue';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';

interface TmdbMovie {
    id: number;
    title: string;
    overview: string;
    poster_path: string | null;
    release_date: string;
    vote_average: number;
    already_imported: boolean;
    is_upcoming?: boolean;
}

interface Props {
    searchResults: TmdbMovie[];
    query: string;
}

const props = defineProps<Props>();

const page = usePage();
const flash = computed(
    () => page.props.flash as { success?: string; error?: string },
);

const queryRef = toRef(props, 'query');
const searchQuery = ref(queryRef.value);
const isSearching = ref(false);

const importForm = useForm({
    tmdb_id: 0,
});

const previewOpen = ref(false);
const previewTmdbId = ref<number | null>(null);

function openPreview(movie: TmdbMovie) {
    previewTmdbId.value = movie.id;
    previewOpen.value = true;
}

function closePreview() {
    previewOpen.value = false;
    previewTmdbId.value = null;
}

function handleImportFromPreview(tmdbId: number) {
    importForm.tmdb_id = tmdbId;
    importForm.post('/tmdb/import-single', {
        preserveScroll: true,
    });
    closePreview();
}

function handleSearch() {
    if (!searchQuery.value.trim()) {
        return;
    }
    isSearching.value = true;
    router.post(
        '/tmdb/search',
        { query: searchQuery.value },
        {
            preserveScroll: true,
            onFinish: () => {
                isSearching.value = false;
            },
        },
    );
}

function handleImport(movie: TmdbMovie, e?: MouseEvent) {
    e?.stopPropagation();
    importForm.tmdb_id = movie.id;
    importForm.post('/tmdb/import-single', {
        preserveScroll: true,
    });
}

function getPosterUrl(posterPath: string | null): string {
    if (!posterPath) {
        return '/images/placeholders/poster-placeholder.png';
    }
    return `https://image.tmdb.org/t/p/w500${posterPath}`;
}
</script>

<template>
    <Head title="TMDB Search - Dashboard" />

    <AppSidebarLayout>
        <div class="w-full px-4 py-12 sm:px-6 lg:px-8">
            <div class="mb-8">
                <!-- Header (matches TmdbImports / Admin pages) -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-white">
                            TMDB Search
                        </h1>
                        <p class="mt-2 text-zinc-400">
                            Search The Movie Database for war movies to import
                        </p>
                    </div>
                </div>

                <!-- Flash messages (matches XSettings / dashboard style) -->
                <div
                    v-if="flash?.success"
                    class="mb-6 rounded-lg bg-green-900/50 p-4 text-green-300 ring-1 ring-green-800"
                >
                    {{ flash.success }}
                </div>
                <div
                    v-if="flash?.error"
                    class="mb-6 rounded-lg bg-red-900/50 p-4 text-red-300 ring-1 ring-red-800"
                >
                    {{ flash.error }}
                </div>

                <!-- Search (matches TmdbImports filter section) -->
                <div class="mb-6 flex flex-col gap-4">
                    <form
                        @submit.prevent="handleSearch"
                        class="flex flex-col gap-4 sm:flex-row sm:items-center"
                    >
                        <div class="relative flex-1">
                            <Search
                                class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-zinc-400"
                            />
                            <Input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search for movies on TMDB..."
                                class="w-full pl-10"
                                :disabled="isSearching"
                            />
                        </div>
                        <Button
                            type="submit"
                            class="shrink-0"
                            :disabled="isSearching || !searchQuery.trim()"
                        >
                            <Search class="size-4" />
                            {{ isSearching ? 'Searching...' : 'Search' }}
                        </Button>
                    </form>
                </div>

                <!-- Search Results (same grid pattern as TmdbMovieGrid) -->
                <div
                    v-if="props.searchResults.length > 0"
                    class="movie-grid grid grid-cols-2 gap-6"
                    style="container-type: inline-size"
                >
                    <div
                        v-for="movie in props.searchResults"
                        :key="movie.id"
                        class="group relative cursor-pointer overflow-hidden rounded-lg border bg-card transition-shadow hover:shadow-lg hover:shadow-black/20"
                        role="button"
                        tabindex="0"
                        @click="openPreview(movie)"
                        @keydown.enter="openPreview(movie)"
                        @keydown.space.prevent="openPreview(movie)"
                    >
                        <div class="relative aspect-2/3">
                            <img
                                :src="getPosterUrl(movie.poster_path)"
                                :alt="movie.title"
                                class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-[1.02]"
                            />
                            <!-- Upcoming badge -->
                            <div
                                v-if="movie.is_upcoming"
                                class="absolute top-3 right-3 z-10"
                            >
                                <UpcomingBadge />
                            </div>
                            <!-- Already imported overlay -->
                            <div
                                v-if="movie.already_imported"
                                class="absolute inset-0 flex items-center justify-center bg-black/70"
                            >
                                <div class="text-center">
                                    <CheckCircle
                                        class="mx-auto size-8 text-green-500"
                                    />
                                    <span
                                        class="mt-2 block text-sm font-medium text-white"
                                        >Already Imported</span
                                    >
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex items-center justify-between gap-2 p-3"
                            @click.stop
                        >
                            <div class="min-w-0 flex-1" @click.stop>
                                <h3
                                    class="mb-0.5 truncate text-sm font-semibold"
                                >
                                    {{ movie.title }}
                                </h3>
                                <p class="text-xs text-muted-foreground">
                                    {{
                                        movie.release_date
                                            ? movie.release_date.substring(0, 4)
                                            : 'Unknown'
                                    }}
                                    <span
                                        v-if="movie.vote_average"
                                        class="ml-2 text-yellow-500"
                                    >
                                        {{ movie.vote_average.toFixed(1) }}
                                    </span>
                                </p>
                            </div>
                            <div class="flex shrink-0">
                                <Button
                                    v-if="!movie.already_imported"
                                    variant="default"
                                    size="sm"
                                    class="gap-1"
                                    :disabled="importForm.processing"
                                    @click="handleImport(movie, $event)"
                                >
                                    <Download class="size-3" />
                                    {{
                                        importForm.processing &&
                                        importForm.tmdb_id === movie.id
                                            ? 'Importing...'
                                            : 'Import'
                                    }}
                                </Button>
                                <Button
                                    v-else
                                    variant="outline"
                                    size="sm"
                                    disabled
                                >
                                    <CheckCircle class="size-3" />
                                    Imported
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TMDB preview dialog -->
                <TmdbSearchPreviewDialog
                    v-model:open="previewOpen"
                    :tmdb-id="previewTmdbId"
                    @import="handleImportFromPreview"
                />

                <!-- No results -->
                <div
                    v-else-if="props.query && props.searchResults.length === 0"
                    class="mb-6 rounded-lg border border-border bg-card py-16 text-center"
                >
                    <p class="text-muted-foreground">
                        No movies found for "{{ props.query }}"
                    </p>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Try a different search term
                    </p>
                </div>

                <!-- Initial state -->
                <div
                    v-else
                    class="mb-6 rounded-lg border border-border bg-card py-16 text-center"
                >
                    <Search class="mx-auto size-12 text-zinc-500" />
                    <p class="mt-4 text-muted-foreground">
                        Search for movies to import from TMDB
                    </p>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Enter a movie title above to get started
                    </p>
                </div>
            </div>
        </div>
    </AppSidebarLayout>
</template>
