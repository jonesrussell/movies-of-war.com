<script setup lang="ts">
import type { Movie } from '@/types/models';

import { Archive, CheckCircle } from 'lucide-vue-next';

import { Poster } from '@/components/primitives';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

interface Props {
    movie: Movie;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'preview', movie: Movie): void;
    (e: 'publish', movie: Movie): void;
    (e: 'archive', movie: Movie): void;
}>();

function handlePreview(e: MouseEvent) {
    if (
        (e.target as HTMLElement).closest('button') ||
        (e.target as HTMLElement).closest('[data-tmdb-action]')
    ) {
        return;
    }
    emit('preview', props.movie);
}

function handlePublish(e: MouseEvent) {
    e.stopPropagation();
    emit('publish', props.movie);
}

function handleArchive(e: MouseEvent) {
    e.stopPropagation();
    emit('archive', props.movie);
}
</script>

<template>
    <div
        class="group relative cursor-pointer overflow-hidden rounded-lg border bg-card transition-shadow hover:shadow-lg hover:shadow-black/20"
        role="button"
        tabindex="0"
        :aria-label="`View details for ${movie.title}`"
        @click="handlePreview"
        @keydown.enter.space.prevent="handlePreview"
    >
        <Poster
            :src="movie.poster_url"
            :alt="movie.title"
            :poster-path="movie.poster_path"
            context="grid"
            aspect-ratio="2/3"
            class="transition-transform duration-200 group-hover:scale-[1.02]"
        />

        <div class="flex items-center justify-between gap-2 p-3">
            <div class="min-w-0 flex-1">
                <h3 class="mb-0.5 truncate text-sm font-semibold">
                    {{ movie.title }}
                </h3>
                <p class="text-xs text-muted-foreground">
                    {{ movie.release_year }}
                </p>
            </div>

            <div
                class="flex shrink-0 gap-1"
                data-tmdb-action
                @click.stop
            >
                <TooltipProvider :delay-duration="200">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button
                                size="icon-sm"
                                variant="default"
                                class="transition-transform hover:scale-110"
                                @click="handlePublish"
                            >
                                <CheckCircle class="size-4" aria-hidden />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Publish</TooltipContent>
                    </Tooltip>
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button
                                size="icon-sm"
                                variant="outline"
                                class="transition-transform hover:scale-110 hover:bg-accent hover:text-accent-foreground"
                                @click="handleArchive"
                            >
                                <Archive class="size-4" aria-hidden />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Archive</TooltipContent>
                    </Tooltip>
                </TooltipProvider>
            </div>
        </div>
    </div>
</template>
