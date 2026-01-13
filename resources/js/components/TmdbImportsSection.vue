<script setup lang="ts">















import { Link, router, useForm } from '@inertiajs/vue3';
import { ArrowRight, Download } from 'lucide-vue-next';
import { ref } from 'vue';

import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

import Pagination from './Pagination.vue';
import SearchInput from './SearchInput.vue';
import TmdbMovieGrid from './TmdbMovieGrid.vue';
import type { Movie, PaginatedMovies } from '@/types/models';

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

const dialogOpen = ref(false);

const form = useForm({
    limit: 30,
    download_posters: false,
});

function handleSearchUpdate(value: string) {
    emit('update:search', value);
}

function handleImport() {
    form.post('/tmdb/import', {
        preserveScroll: true,
        onSuccess: () => {
            dialogOpen.value = false;
            form.reset();
        },
    });
}
</script>

<template>
    <div id="tmdb-imports" class="mb-8">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold">TMDB Imports</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ props.tmdbDrafts?.meta?.total ?? 0 }} movies awaiting review
                </p>
            </div>

            <div class="flex items-center gap-3">
                <Dialog v-model:open="dialogOpen">
                    <DialogTrigger as-child>
                        <Button variant="default">
                            <Download class="size-4" />
                            Import Movies
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Import TMDB Movies</DialogTitle>
                            <DialogDescription>
                                Import war movies from The Movie Database. The import will run in the background.
                            </DialogDescription>
                        </DialogHeader>
                        <form @submit.prevent="handleImport" class="space-y-4">
                            <div class="space-y-2">
                                <Label for="limit">Number of movies to import</Label>
                                <Input
                                    id="limit"
                                    v-model.number="form.limit"
                                    type="number"
                                    min="1"
                                    max="100"
                                    :disabled="form.processing"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Enter a number between 1 and 100
                                </p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <Checkbox
                                    id="download_posters"
                                    v-model:checked="form.download_posters"
                                    :disabled="form.processing"
                                />
                                <Label for="download_posters" class="cursor-pointer">
                                    Download poster images
                                </Label>
                            </div>
                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="dialogOpen = false"
                                    :disabled="form.processing"
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" :disabled="form.processing">
                                    {{ form.processing ? 'Importing...' : 'Start Import' }}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>

                <Link
                    href="/featured-slots"
                    class="text-sm text-primary hover:text-primary/80 flex items-center gap-1"
                >
                    Manage Featured Slots
                    <ArrowRight class="size-4" />
                </Link>
            </div>
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
        </div>

        <!-- Pagination -->
        <div v-if="props.tmdbDrafts?.meta && props.tmdbDrafts.meta.last_page > 1" class="mb-6">
            <Pagination :meta="props.tmdbDrafts.meta" />
        </div>

        <div v-if="!props.tmdbDrafts?.data || props.tmdbDrafts.data.length === 0" class="rounded-lg border border-border bg-card py-16 text-center">
            <p class="text-muted-foreground">No TMDB imports found</p>
            <p class="mt-2 text-sm text-muted-foreground">
                Click "Import Movies" above to import movies from TMDB
            </p>
        </div>
    </div>
</template>
