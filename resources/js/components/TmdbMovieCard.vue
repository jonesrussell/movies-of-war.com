<script setup lang="ts">
import type { Movie } from '@/types/models';

import { Link } from '@inertiajs/vue3';
import { Archive, CheckCircle } from 'lucide-vue-next';
import { computed } from 'vue';

import { Poster } from '@/components/primitives';
import UpcomingBadge from '@/components/UpcomingBadge.vue';
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
    (e: 'publish', movie: Movie): void;
    (e: 'archive', movie: Movie): void;
}>();

const detailsUrl = computed(
    () => `/dashboard/movies/${props.movie.id}?from=imports`,
);

function handlePublish(e: MouseEvent) {
    e.preventDefault();
    e.stopPropagation();
    emit('publish', props.movie);
}

function handleArchive(e: MouseEvent) {
    e.preventDefault();
    e.stopPropagation();
    emit('archive', props.movie);
}
</script>

<template>
    <div
        class="group relative overflow-hidden rounded-lg border bg-card transition-shadow hover:shadow-lg hover:shadow-black/20"
    >
        <Link
            :href="detailsUrl"
            class="block focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
        >
            <Poster
                :src="movie.poster_url"
                :alt="movie.title"
                :poster-path="movie.poster_path"
                context="grid"
                aspect-ratio="2/3"
                class="transition-transform duration-200 group-hover:scale-[1.02]"
            >
                <div
                    v-if="movie.is_upcoming"
                    class="absolute top-3 right-3 z-20"
                >
                    <UpcomingBadge />
                </div>
            </Poster>
        </Link>

        <div class="flex items-center justify-between gap-2 p-3">
            <Link
                :href="detailsUrl"
                class="min-w-0 flex-1 rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            >
                <h3
                    class="mb-0.5 truncate text-sm font-semibold hover:underline"
                >
                    {{ movie.title }}
                </h3>
                <p class="text-xs text-muted-foreground">
                    {{ movie.release_year }}
                </p>
            </Link>

            <div class="flex shrink-0 gap-1" data-tmdb-action @click.stop>
                <TooltipProvider :delay-duration="200">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button
                                size="icon-sm"
                                variant="default"
                                title="Publish"
                                class="hover:bg-green-700"
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
                                title="Archive"
                                class="hover:border-zinc-500 hover:bg-zinc-800"
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
