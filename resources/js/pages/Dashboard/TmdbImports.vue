<script setup lang="ts">















import { Head, router } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { Download } from 'lucide-vue-next';
import { ref } from 'vue';

import ConfirmDialog from '@/components/ConfirmDialog.vue';
import Pagination from '@/components/Pagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import TmdbMovieGrid from '@/components/TmdbMovieGrid.vue';
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
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { Movie, PaginatedMovies } from '@/types/models';

interface Props {
    tmdbDrafts: PaginatedMovies | { data: Movie[]; meta?: PaginatedMovies['meta'] };
    queryParams: {
        search?: string;
    };
}

const props = defineProps<Props>();

const search = ref(props.queryParams.search || '');
const confirmDialogOpen = ref(false);
const pendingAction = ref<{ type: 'publish' | 'archive'; movie: Movie } | null>(null);
const dialogOpen = ref(false);

const form = useForm({
    limit: 30,
    download_posters: false,
});

function handleSearchUpdate(value: string) {
    search.value = value;
    router.get('/dashboard/tmdb-imports', {
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
    <Head title="TMDB Imports - Dashboard" />

    <AppSidebarLayout>
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="mb-8">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold">TMDB Imports</h1>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ props.tmdbDrafts?.meta?.total ?? 0 }} movies awaiting review
                        </p>
                    </div>

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
                        @publish="publishMovie"
                        @archive="archiveMovie"
                    />
                </div>

                <div v-if="!props.tmdbDrafts?.data || props.tmdbDrafts.data.length === 0" class="mb-6 rounded-lg border border-border bg-card py-16 text-center">
                    <p class="text-muted-foreground">No TMDB imports found</p>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Click "Import Movies" above to import movies from TMDB
                    </p>
                </div>

                <!-- Pagination -->
                <div v-if="props.tmdbDrafts?.meta && props.tmdbDrafts.meta.last_page > 1">
                    <Pagination :meta="props.tmdbDrafts.meta" />
                </div>
            </div>
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
