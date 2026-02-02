<script setup lang="ts">
import type { Movie } from '@/types/models';

import {
    Archive,
    Calendar,
    CheckCircle,
    Clock,
    Globe,
    Play,
    Swords,
} from 'lucide-vue-next';

import { Poster } from '@/components/primitives';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

interface Props {
    movie: Movie | null;
    open: boolean;
}

defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    publish: [movie: Movie];
    archive: [movie: Movie];
}>();

function openTrailer(movie: Movie) {
    if (movie?.trailer_url) {
        window.open(movie.trailer_url, '_blank');
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent
            class="max-w-2xl border-zinc-800 bg-zinc-950 p-0 text-white"
        >
            <div v-if="movie" class="flex flex-col sm:flex-row">
                <!-- Poster -->
                <div class="shrink-0 sm:w-48">
                    <Poster
                        :src="movie.poster_url"
                        :alt="movie.title"
                        :poster-path="movie.poster_path"
                        context="grid"
                        aspect-ratio="2/3"
                        class="rounded-t-lg sm:rounded-l-lg sm:rounded-tr-none"
                    />
                </div>

                <!-- Content -->
                <div class="flex flex-1 flex-col p-5">
                    <DialogHeader class="mb-4 text-left">
                        <DialogTitle class="text-xl font-bold text-white">
                            {{ movie.title }}
                        </DialogTitle>
                        <DialogDescription class="sr-only">
                            Preview details for {{ movie.title }}
                        </DialogDescription>
                    </DialogHeader>

                    <!-- Meta info -->
                    <div
                        class="mb-4 flex flex-wrap gap-3 text-sm text-zinc-400"
                    >
                        <span
                            v-if="movie.release_year"
                            class="flex items-center gap-1"
                        >
                            <Calendar class="size-4" />
                            {{ movie.release_year }}
                        </span>
                        <span
                            v-if="movie.runtime"
                            class="flex items-center gap-1"
                        >
                            <Clock class="size-4" />
                            {{ movie.runtime }} min
                        </span>
                        <span
                            v-if="movie.country"
                            class="flex items-center gap-1"
                        >
                            <Globe class="size-4" />
                            {{ movie.country }}
                        </span>
                        <span
                            v-if="movie.conflict"
                            class="flex items-center gap-1"
                        >
                            <Swords class="size-4" />
                            {{ movie.conflict }}
                        </span>
                    </div>

                    <!-- Tags -->
                    <div
                        v-if="movie.tags && movie.tags.length > 0"
                        class="mb-4 flex flex-wrap gap-1"
                    >
                        <Badge
                            v-for="tag in movie.tags.slice(0, 5)"
                            :key="tag.id"
                            variant="secondary"
                            class="bg-zinc-800 text-zinc-300"
                        >
                            {{ tag.name }}
                        </Badge>
                    </div>

                    <!-- Synopsis -->
                    <p
                        v-if="movie.synopsis"
                        class="mb-6 line-clamp-4 flex-1 text-sm text-zinc-300"
                    >
                        {{ movie.synopsis }}
                    </p>

                    <!-- Actions -->
                    <DialogFooter class="flex flex-wrap gap-2 sm:justify-start">
                        <Button
                            v-if="movie.trailer_url"
                            variant="outline"
                            size="sm"
                            class="border-zinc-700 text-zinc-200 hover:bg-zinc-800 hover:text-white"
                            @click="openTrailer(movie)"
                        >
                            <Play class="mr-2 size-4" />
                            Trailer
                        </Button>
                        <Button
                            variant="default"
                            size="sm"
                            class="bg-green-600 text-white hover:bg-green-500"
                            @click="emit('publish', movie)"
                        >
                            <CheckCircle class="mr-2 size-4" />
                            Publish
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            class="border-zinc-700 text-zinc-200 hover:bg-red-950/50 hover:border-red-900 hover:text-red-400"
                            @click="emit('archive', movie)"
                        >
                            <Archive class="mr-2 size-4" />
                            Archive
                        </Button>
                    </DialogFooter>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
