<script setup lang="ts">
import type { Movie } from '@/types';

import { Link, router, usePage } from '@inertiajs/vue3';
import {
    Bookmark,
    BookmarkCheck,
    Calendar,
    Clock,
    Eye,
    Globe,
    Loader2,
    Swords,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import { Poster } from '@/components/primitives';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import UpcomingBadge from '@/components/UpcomingBadge.vue';
import { formatReleaseDate } from '@/utils/format';

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

const displayedTags = computed(() => {
    const tags = props.movie.tags;
    if (!tags || !Array.isArray(tags) || typeof tags.slice !== 'function') {
        return [];
    }
    return tags.slice(0, 4);
});

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
</script>

<template>
    <article
        class="group flex gap-4 border-b border-[--intel-border] p-3 transition-colors duration-150 hover:bg-[--intel-bg-elevated]"
    >
        <!-- Poster -->
        <Link
            :href="`/movies/${movie.slug}`"
            class="shrink-0 overflow-hidden rounded-lg"
        >
            <Poster
                :src="movie.poster_url"
                :alt="movie.title"
                :poster-path="movie.poster_path"
                context="grid"
                aspect-ratio="2/3"
                class="h-32 w-auto transition-transform duration-300 group-hover:scale-105"
            >
                <div
                    v-if="movie.is_upcoming"
                    class="absolute top-2 right-2 z-10"
                >
                    <UpcomingBadge size="compact" />
                </div>
            </Poster>
        </Link>

        <!-- Content -->
        <div class="flex min-w-0 flex-1 flex-col justify-between py-1">
            <div>
                <!-- Title -->
                <h3 class="mb-1 font-semibold text-[--intel-text-primary]">
                    <Link
                        :href="`/movies/${movie.slug}`"
                        class="transition-colors hover:text-blue-400"
                    >
                        {{ movie.title }}
                    </Link>
                </h3>

                <!-- Meta info -->
                <div
                    class="mb-2 flex flex-wrap items-center gap-x-3 gap-y-1 font-[family-name:var(--font-mono-display)] text-xs text-[--intel-text-muted]"
                >
                    <span
                        v-if="movie.release_date || movie.release_year != null"
                        class="flex items-center gap-1"
                    >
                        <Calendar class="size-3" />
                        {{
                            formatReleaseDate(
                                movie.release_date,
                                movie.release_year,
                            )
                        }}
                    </span>
                    <span v-if="movie.runtime" class="flex items-center gap-1">
                        <Clock class="size-3" />
                        {{ movie.runtime }} min
                    </span>
                    <span v-if="movie.country" class="flex items-center gap-1">
                        <Globe class="size-3" />
                        {{ movie.country }}
                    </span>
                    <span v-if="movie.conflict" class="flex items-center gap-1">
                        <Swords class="size-3" />
                        {{ movie.conflict }}
                    </span>
                </div>

                <!-- Synopsis -->
                <p
                    v-if="movie.synopsis"
                    class="mb-2 line-clamp-2 text-sm text-[--intel-text-body]"
                >
                    {{ movie.synopsis }}
                </p>

                <!-- Tags -->
                <div
                    v-if="displayedTags.length > 0"
                    class="flex flex-wrap gap-1"
                >
                    <Badge
                        v-for="tag in displayedTags"
                        :key="tag.id"
                        variant="secondary"
                        class="bg-[--intel-bg-elevated] px-2 py-0 text-[10px] text-[--intel-text-body]"
                    >
                        {{ tag.name }}
                    </Badge>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-2 flex gap-2">
                <Button
                    variant="ghost"
                    size="sm"
                    class="h-8 px-2 text-[--intel-text-muted] hover:text-[--intel-text-primary]"
                    :disabled="isToggling"
                    @click="toggleWatchlist"
                >
                    <Loader2 v-if="isToggling" class="size-4 animate-spin" />
                    <BookmarkCheck
                        v-else-if="isCurrentlyWatchlisted"
                        class="size-4 text-blue-500"
                    />
                    <Bookmark v-else class="size-4" />
                </Button>

                <Button
                    variant="ghost"
                    size="sm"
                    class="h-8 px-2 text-[--intel-text-muted] hover:text-[--intel-text-primary]"
                    @click="emit('preview')"
                >
                    <Eye class="size-4" />
                </Button>
            </div>
        </div>
    </article>
</template>
