<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import SearchInput from './SearchInput.vue';
import TmdbMovieGrid from './TmdbMovieGrid.vue';
import Pagination from './Pagination.vue';
import type { Movie, PaginatedMovies } from '@/types/models';
import { ArrowRight } from 'lucide-vue-next';

interface Props {
    tmdbDrafts: PaginatedMovies | { data: Movie[]; meta?: PaginatedMovies['meta'] };
    search?: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'update:search', value: string): void;
    (e: 'publish', movie: Movie): void;
    (e: 'archive', movie: Movie): void;
}>();

function handleSearchUpdate(value: string) {
    emit('update:search', value);
}
</script>

<template>
    <div class="mb-8">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold">TMDB Imports</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ props.tmdbDrafts?.meta?.total ?? 0 }} movies awaiting review
                </p>
            </div>

            <Link
                href="/featured-slots"
                class="text-sm text-primary hover:text-primary/80 flex items-center gap-1"
            >
                Manage Featured Slots
                <ArrowRight class="size-4" />
            </Link>
        </div>

        <!-- Search -->
        <div class="mb-6">
            <SearchInput
                :model-value="search"
                placeholder="Search TMDB imports..."
                @update:model-value="handleSearchUpdate"
            />
        </div>

        <!-- TMDB Movies Grid -->
        <div v-if="props.tmdbDrafts?.data && props.tmdbDrafts.data.length > 0" class="mb-6">
            <TmdbMovieGrid
                :movies="props.tmdbDrafts.data"
                @publish="emit('publish', $event)"
                @archive="emit('archive', $event)"
            />

            <!-- Pagination -->
            <Pagination
                v-if="props.tmdbDrafts?.meta && props.tmdbDrafts.meta.last_page > 1"
                :meta="props.tmdbDrafts.meta"
            />
        </div>

        <div v-else class="rounded-lg border border-border bg-card py-16 text-center">
            <p class="text-muted-foreground">No TMDB imports found</p>
            <p class="mt-2 text-sm text-muted-foreground">
                Run <code class="rounded bg-muted px-2 py-1">php artisan tmdb:import</code> to import movies
            </p>
        </div>
    </div>
</template>
