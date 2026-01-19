<script setup lang="ts">
import type { Movie } from '@/types';

import { router, usePage } from '@inertiajs/vue3';
import { Bookmark, BookmarkCheck, Eye, Loader2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

interface Props {
    movie: Movie;
    isWatchlisted?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    isWatchlisted: false,
});

const emit = defineEmits<{
    preview: [];
}>();

const page = usePage();
const isAuthenticated = computed(
    () => !!(page.props.auth as { user: unknown } | undefined)?.user,
);

const isToggling = ref(false);
const localWatchlisted = ref(false);

// Sync with props
watch(
    () => props.isWatchlisted,
    (newValue) => {
        localWatchlisted.value = newValue;
    },
    { immediate: true },
);

const isCurrentlyWatchlisted = computed(() => localWatchlisted.value);

async function toggleWatchlist() {
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

function openPreview() {
    emit('preview');
}
</script>

<template>
    <div
        class="absolute inset-0 z-10 flex items-center justify-center gap-2 bg-black/70 opacity-0 transition-opacity duration-200 group-hover:opacity-100"
    >
        <TooltipProvider :delay-duration="200">
            <Tooltip>
                <TooltipTrigger as-child>
                    <Button
                        size="icon"
                        variant="secondary"
                        class="size-10 rounded-full"
                        :disabled="isToggling"
                        @click.prevent.stop="toggleWatchlist"
                    >
                        <Loader2
                            v-if="isToggling"
                            class="size-5 animate-spin"
                        />
                        <BookmarkCheck
                            v-else-if="isCurrentlyWatchlisted"
                            class="size-5 text-red-500"
                        />
                        <Bookmark v-else class="size-5" />
                    </Button>
                </TooltipTrigger>
                <TooltipContent side="bottom">
                    <p>
                        {{
                            isCurrentlyWatchlisted
                                ? 'Remove from watchlist'
                                : isAuthenticated
                                  ? 'Add to watchlist'
                                  : 'Login to add to watchlist'
                        }}
                    </p>
                </TooltipContent>
            </Tooltip>

            <Tooltip>
                <TooltipTrigger as-child>
                    <Button
                        size="icon"
                        variant="secondary"
                        class="size-10 rounded-full"
                        @click.prevent.stop="openPreview"
                    >
                        <Eye class="size-5" />
                    </Button>
                </TooltipTrigger>
                <TooltipContent side="bottom">
                    <p>Quick preview</p>
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>
    </div>
</template>
