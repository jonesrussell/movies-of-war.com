<script setup lang="ts">
import type { Movie, PaginatedMovies } from '@/types/models';

import { Head, router, useForm } from '@inertiajs/vue3';
import { Download } from 'lucide-vue-next';
import { computed, ref } from 'vue';

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

const PER_PAGE_OPTIONS = [10, 20, 24, 50, 100] as const;

interface Props {
    tmdbDrafts:
        | PaginatedMovies
        | { data: Movie[]; meta?: PaginatedMovies['meta'] };
    queryParams: {
        search?: string;
        sort?: string;
        per_page?: number;
    };
}

const props = defineProps<Props>();

const queryParams = computed(() => props.queryParams ?? {});
const search = ref(queryParams.value?.search ?? '');
const sortFilter = ref(queryParams.value?.sort ?? 'updated_at_desc');
const perPage = ref(queryParams.value?.per_page ?? 24);
const confirmDialogOpen = ref(false);
const pendingAction = ref<{ type: 'publish' | 'archive'; movie: Movie } | null>(
    null,
);
const dialogOpen = ref(false);

const form = useForm({
    limit: 30,
    upcoming: false,
});

function applyFilters(
    updates: Record<string, string | number | undefined> = {},
) {
    const resetPage = ['search', 'sort', 'per_page'].some(
        (k) => updates[k] !== undefined,
    );
    router.get(
        '/dashboard/tmdb/imports',
        {
            search: search.value || undefined,
            sort: sortFilter.value || undefined,
            per_page: perPage.value,
            ...updates,
            ...(resetPage ? { page: 1 } : {}),
        } as Record<string, string | number | undefined>,
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
}

function handleSearchUpdate(value: string) {
    search.value = value;
    applyFilters({ search: value || undefined });
}

function setSort(s: string) {
    sortFilter.value = s;
    applyFilters({ sort: s || undefined });
}

function setPerPage(n: number) {
    perPage.value = n;
    applyFilters({ per_page: n });
}

const paginationSummary = computed(() => {
    const m = props.tmdbDrafts?.meta;
    if (!m || m.total === 0) return null;
    const from = m.from ?? 0;
    const to = m.to ?? 0;
    const total = m.total;
    return `Showing ${from}–${to} of ${total}`;
});

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

    router.post(
        `/movies/${movie.id}/${action}`,
        {},
        {
            preserveScroll: true,
        },
    );
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
        <div class="w-full px-4 py-12 sm:px-6 lg:px-8">
            <div class="mb-8">
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold">TMDB Imports</h1>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ props.tmdbDrafts?.meta?.total ?? 0 }} movies
                            awaiting review
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
                                    Import war movies from The Movie Database.
                                    The import will run in the background.
                                </DialogDescription>
                            </DialogHeader>
                            <form
                                @submit.prevent="handleImport"
                                class="space-y-4"
                            >
                                <div class="space-y-2">
                                    <Label for="limit"
                                        >Number of movies to import</Label
                                    >
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
                                        id="upcoming"
                                        :checked="form.upcoming"
                                        @update:checked="
                                            (value: unknown) =>
                                                (form.upcoming = value === true)
                                        "
                                        @update:modelValue="
                                            (value: unknown) =>
                                                (form.upcoming = value === true)
                                        "
                                        :disabled="form.processing"
                                    />
                                    <Label
                                        for="upcoming"
                                        class="cursor-pointer"
                                    >
                                        Upcoming releases only
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
                                    <Button
                                        type="submit"
                                        :disabled="form.processing"
                                    >
                                        {{
                                            form.processing
                                                ? 'Importing...'
                                                : 'Start Import'
                                        }}
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                <!-- Search and filters -->
                <div class="mb-6 flex flex-col gap-4">
                    <SearchInput
                        :model-value="search"
                        placeholder="Search TMDB imports..."
                        @update:model-value="handleSearchUpdate"
                    />
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="flex items-center gap-2">
                            <label for="tmdb-sort" class="text-sm text-zinc-400"
                                >Sort</label
                            >
                            <select
                                id="tmdb-sort"
                                :value="sortFilter"
                                class="rounded-lg border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-white focus:border-red-500 focus:ring-red-500"
                                @change="
                                    setSort(
                                        ($event.target as HTMLSelectElement)
                                            .value,
                                    )
                                "
                            >
                                <option value="updated_at_desc">
                                    Newest first
                                </option>
                                <option value="updated_at_asc">
                                    Oldest first
                                </option>
                                <option value="title_asc">Title A–Z</option>
                                <option value="title_desc">Title Z–A</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <label
                                for="tmdb-per-page"
                                class="text-sm text-zinc-400"
                                >Per page</label
                            >
                            <select
                                id="tmdb-per-page"
                                :value="perPage"
                                class="rounded-lg border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-white focus:border-red-500 focus:ring-red-500"
                                @change="
                                    setPerPage(
                                        Number(
                                            ($event.target as HTMLSelectElement)
                                                .value,
                                        ),
                                    )
                                "
                            >
                                <option
                                    v-for="n in PER_PAGE_OPTIONS"
                                    :key="n"
                                    :value="n"
                                >
                                    {{ n }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Pagination summary -->
                <div
                    v-if="paginationSummary"
                    class="mb-2 text-sm text-zinc-400"
                >
                    {{ paginationSummary }}
                </div>

                <!-- TMDB Movies Grid -->
                <div
                    v-if="
                        props.tmdbDrafts?.data &&
                        props.tmdbDrafts.data.length > 0
                    "
                    class="mb-6"
                >
                    <TmdbMovieGrid
                        :movies="props.tmdbDrafts.data"
                        @publish="publishMovie"
                        @archive="archiveMovie"
                    />
                </div>

                <div
                    v-if="
                        !props.tmdbDrafts?.data ||
                        props.tmdbDrafts.data.length === 0
                    "
                    class="mb-6 rounded-lg border border-border bg-card py-16 text-center"
                >
                    <p class="text-muted-foreground">No TMDB imports found</p>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Click "Import Movies" above to import movies from TMDB
                    </p>
                </div>

                <!-- Pagination -->
                <div
                    v-if="
                        props.tmdbDrafts?.meta &&
                        props.tmdbDrafts.meta.last_page > 1
                    "
                >
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
            :confirm-text="
                pendingAction.type === 'publish' ? 'Publish' : 'Archive'
            "
            :variant="
                pendingAction.type === 'archive' ? 'destructive' : 'default'
            "
            @confirm="handleConfirm"
            @cancel="handleCancel"
        />
    </AppSidebarLayout>
</template>
