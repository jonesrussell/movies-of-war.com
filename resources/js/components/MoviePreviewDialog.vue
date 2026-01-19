<script setup lang="ts">
import type { Movie } from '@/types';

import { Link, router, usePage } from '@inertiajs/vue3';
import {
    Bookmark,
    BookmarkCheck,
    Calendar,
    Clock,
    ExternalLink,
    Globe,
    Loader2,
    Play,
    Swords,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import { Poster } from '@/components/primitives';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

interface Props {
    movie: Movie | null;
    open: boolean;
    isWatchlisted?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    isWatchlisted: false,
});

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const page = usePage();
const isAuthenticated = computed(
    () => !!(page.props.auth as { user: unknown } | undefined)?.user,
);

const isToggling = ref(false);
const localWatchlisted = ref(false);

watch(
    () => props.isWatchlisted,
    (newValue) => {
        localWatchlisted.value = newValue;
    },
    { immediate: true },
);

const isCurrentlyWatchlisted = computed(() => localWatchlisted.value);

async function toggleWatchlist() {
    if (!props.movie) return;

    if (!isAuthenticated.value) {
        router.visit('/login');
        return;
    }

    if (isToggling.value) return;

    isToggling.value = true;
    const wasWatchlisted = localWatchlisted.value;
    localWatchlisted.value = !wasWatchlisted;

    const url = `/watchlist/${props.movie.id}`;
    const method = wasWatchlisted ? 'delete' : 'post';

    router[method](
        url,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                localWatchlisted.value = wasWatchlisted;
            },
            onFinish: () => {
                isToggling.value = false;
            },
        },
    );
}

function openTrailer() {
    if (props.movie?.trailer_url) {
        window.open(props.movie.trailer_url, '_blank');
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
                            Preview information for {{ movie.title }}
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
                    <div class="flex flex-wrap gap-2">
                        <Button as-child variant="default" size="sm">
                            <Link :href="`/movies/${movie.slug}`">
                                <ExternalLink class="mr-2 size-4" />
                                View Details
                            </Link>
                        </Button>

                        <Button
                            v-if="movie.trailer_url"
                            variant="outline"
                            size="sm"
                            @click="openTrailer"
                        >
                            <Play class="mr-2 size-4" />
                            Trailer
                        </Button>

                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="isToggling"
                            @click="toggleWatchlist"
                        >
                            <Loader2
                                v-if="isToggling"
                                class="mr-2 size-4 animate-spin"
                            />
                            <BookmarkCheck
                                v-else-if="isCurrentlyWatchlisted"
                                class="mr-2 size-4 text-red-500"
                            />
                            <Bookmark v-else class="mr-2 size-4" />
                            {{
                                isCurrentlyWatchlisted
                                    ? 'In Watchlist'
                                    : 'Add to Watchlist'
                            }}
                        </Button>
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
