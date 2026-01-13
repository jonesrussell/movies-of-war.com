<script setup lang="ts">















import { CheckCircle, Archive } from 'lucide-vue-next';

import { Button } from '@/components/ui/button';
import type { Movie } from '@/types/models';

interface Props {
    movie: Movie;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'publish', movie: Movie): void;
    (e: 'archive', movie: Movie): void;
}>();

function handlePublish() {
    emit('publish', props.movie);
}

function handleArchive() {
    emit('archive', props.movie);
}
</script>

<template>
    <div class="group relative overflow-hidden rounded-lg bg-card border">
        <div class="aspect-[2/3]">
            <img
                :src="movie.poster_url || '/images/placeholders/poster-placeholder.png'"
                :alt="movie.title"
                class="h-full w-full object-cover"
            />
        </div>

        <div class="p-3">
            <h3 class="mb-1 truncate text-sm font-semibold">
                {{ movie.title }}
            </h3>
            <p class="mb-3 text-xs text-muted-foreground">
                {{ movie.release_year }}
            </p>

            <div class="flex gap-2">
                <Button
                    @click="handlePublish"
                    variant="default"
                    size="sm"
                    class="flex flex-1 items-center justify-center gap-1"
                >
                    <CheckCircle class="size-3" />
                    Publish
                </Button>

                <Button
                    @click="handleArchive"
                    variant="outline"
                    size="sm"
                    class="flex flex-1 items-center justify-center gap-1"
                >
                    <Archive class="size-3" />
                    Archive
                </Button>
            </div>
        </div>
    </div>
</template>
