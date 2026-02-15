<script setup lang="ts">
import {
    Calendar,
    CheckCircle,
    Clock,
    Loader2,
    Play,
    Star,
    User,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

interface TmdbPreview {
    id: number;
    title: string;
    overview: string | null;
    release_date: string | null;
    runtime: number | null;
    poster_path: string | null;
    vote_average: number | null;
    vote_count: number | null;
    director: string | null;
    writers: string[];
    genres: Array<{ id: number; name: string }>;
    trailer_url: string | null;
    already_imported: boolean;
    is_upcoming?: boolean;
}

interface Props {
    open: boolean;
    tmdbId: number | null;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    import: [tmdbId: number];
}>();

const preview = ref<TmdbPreview | null>(null);
const loading = ref(false);
const error = ref<string | null>(null);

function getPosterUrl(posterPath: string | null): string {
    if (!posterPath) {
        return '/images/placeholders/poster-placeholder.png';
    }
    return `https://image.tmdb.org/t/p/w500${posterPath}`;
}

const releaseYear = computed(() => {
    const d = preview.value?.release_date;
    if (!d) return null;
    return d.substring(0, 4);
});

async function fetchPreview(id: number) {
    preview.value = null;
    error.value = null;
    loading.value = true;
    try {
        const res = await fetch(`/dashboard/tmdb/preview/${id}`, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            error.value = data.error ?? 'Could not load movie details';
            return;
        }
        preview.value = (await res.json()) as TmdbPreview;
    } catch {
        error.value = 'Could not load movie details';
    } finally {
        loading.value = false;
    }
}

watch(
    () => [props.open, props.tmdbId] as const,
    ([open, id]) => {
        if (open && id) {
            void fetchPreview(id);
        } else if (!open) {
            preview.value = null;
            error.value = null;
        }
    },
    { immediate: true },
);

function openTrailer() {
    if (preview.value?.trailer_url) {
        window.open(preview.value.trailer_url, '_blank');
    }
}

function handleImport() {
    if (preview.value && !preview.value.already_imported) {
        emit('import', preview.value.id);
        emit('update:open', false);
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent
            class="max-w-2xl border-zinc-800 bg-zinc-950 p-0 text-white"
        >
            <div v-if="loading" class="flex min-h-[280px] items-center justify-center p-8">
                <Loader2 class="size-10 animate-spin text-zinc-400" />
            </div>
            <div v-else-if="error" class="p-6 text-center">
                <p class="text-red-400">{{ error }}</p>
            </div>
            <div v-else-if="preview" class="flex flex-col sm:flex-row">
                <!-- Poster -->
                <div class="relative shrink-0 sm:w-48">
                    <img
                        :src="getPosterUrl(preview.poster_path)"
                        :alt="preview.title"
                        class="h-full w-full rounded-t-lg object-cover sm:rounded-l-lg sm:rounded-tr-none"
                    />
                </div>

                <!-- Content -->
                <div class="flex flex-1 flex-col p-5">
                    <DialogHeader class="mb-4 text-left">
                        <DialogTitle class="text-xl font-bold text-white">
                            {{ preview.title }}
                        </DialogTitle>
                        <DialogDescription class="sr-only">
                            TMDB preview for {{ preview.title }}
                        </DialogDescription>
                    </DialogHeader>

                    <!-- Meta -->
                    <div class="mb-4 flex flex-wrap gap-3 text-sm text-zinc-400">
                        <span v-if="releaseYear" class="flex items-center gap-1">
                            <Calendar class="size-4" />
                            {{ releaseYear }}
                        </span>
                        <span v-if="preview.runtime" class="flex items-center gap-1">
                            <Clock class="size-4" />
                            {{ preview.runtime }} min
                        </span>
                        <span
                            v-if="preview.vote_average != null"
                            class="flex items-center gap-1"
                        >
                            <Star class="size-4 fill-yellow-500 text-yellow-500" />
                            {{ preview.vote_average.toFixed(1) }}
                            <span v-if="preview.vote_count" class="text-zinc-500">
                                ({{ preview.vote_count }})
                            </span>
                        </span>
                    </div>

                    <div v-if="preview.director" class="mb-2 flex items-center gap-1 text-sm text-zinc-400">
                        <User class="size-4" />
                        {{ preview.director }}
                    </div>
                    <div
                        v-if="preview.writers && preview.writers.length > 0"
                        class="mb-4 text-xs text-zinc-500"
                    >
                        Writers: {{ preview.writers.join(', ') }}
                    </div>

                    <!-- Genres -->
                    <div
                        v-if="preview.genres?.length > 0"
                        class="mb-4 flex flex-wrap gap-1"
                    >
                        <span
                            v-for="g in preview.genres.slice(0, 5)"
                            :key="g.id"
                            class="rounded bg-zinc-800 px-2 py-0.5 text-xs text-zinc-300"
                        >
                            {{ g.name }}
                        </span>
                    </div>

                    <!-- Overview -->
                    <p
                        v-if="preview.overview"
                        class="mb-6 flex-1 text-sm text-zinc-300"
                    >
                        {{ preview.overview }}
                    </p>

                    <!-- Actions -->
                    <DialogFooter class="flex flex-wrap gap-2 sm:justify-start">
                        <Button
                            v-if="preview.trailer_url"
                            variant="outline"
                            size="sm"
                            class="border-zinc-700 text-zinc-200 hover:bg-zinc-800 hover:text-white"
                            @click="openTrailer"
                        >
                            <Play class="mr-2 size-4" />
                            Trailer
                        </Button>
                        <Button
                            v-if="!preview.already_imported"
                            variant="default"
                            size="sm"
                            class="bg-green-600 text-white hover:bg-green-500"
                            :disabled="preview.already_imported"
                            @click="handleImport"
                        >
                            <CheckCircle class="mr-2 size-4" />
                            Import
                        </Button>
                        <Button
                            v-else
                            variant="outline"
                            size="sm"
                            disabled
                        >
                            <CheckCircle class="mr-2 size-4" />
                            Already imported
                        </Button>
                    </DialogFooter>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
