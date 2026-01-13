<script setup lang="ts">





import { Link, router, usePage } from '@inertiajs/vue3';
import { CheckCircle, Archive, XCircle } from 'lucide-vue-next';

import { Button } from '@/components/ui/button';
import type { Movie } from '@/types';

interface Props {
    movie: Movie;
}

const props = defineProps<Props>();
const page = usePage();
const auth = page.props.auth as { user: any };

const posterImage = props.movie.poster_url || '/images/placeholders/poster-placeholder.png';
const isAdmin = auth?.user?.is_admin;

function handlePublish(e: Event) {
    e.preventDefault();
    e.stopPropagation();
    router.post(`/movies/${props.movie.id}/publish`, {}, {
        preserveScroll: true,
    });
}

function handleUnpublish(e: Event) {
    e.preventDefault();
    e.stopPropagation();
    router.post(`/movies/${props.movie.id}/unpublish`, {}, {
        preserveScroll: true,
    });
}

function handleArchive(e: Event) {
    e.preventDefault();
    e.stopPropagation();
    if (confirm(`Archive "${props.movie.title}"?`)) {
        router.post(`/movies/${props.movie.id}/archive`, {}, {
            preserveScroll: true,
        });
    }
}

const isPublished = props.movie.status === 'published';
</script>

<template>
    <div class="group relative block overflow-hidden rounded-lg bg-zinc-900 transition-transform hover:scale-[1.02]">
        <Link
            :href="`/movies/${movie.slug}`"
            class="block"
        >
            <div class="aspect-[2/3] overflow-hidden">
                <img
                    :src="posterImage"
                    :alt="movie.title"
                    class="h-full w-full object-cover transition-transform group-hover:scale-105"
                    loading="lazy"
                />
            </div>

            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent opacity-0 transition-opacity group-hover:opacity-100">
                <div class="absolute bottom-0 left-0 right-0 p-4">
                    <h3 class="mb-2 text-lg font-bold text-white">{{ movie.title }}</h3>

                    <div class="mb-2 flex items-center gap-2 text-sm text-zinc-300">
                        <span>{{ movie.release_year }}</span>
                        <span v-if="movie.runtime" class="flex items-center gap-1">
                            • {{ movie.runtime }} min
                        </span>
                        <span v-if="movie.country">• {{ movie.country }}</span>
                    </div>

                    <p v-if="movie.synopsis" class="line-clamp-2 text-sm text-zinc-400">
                        {{ movie.synopsis }}
                    </p>

                    <div v-if="movie.tags && movie.tags.length > 0" class="mt-2 flex flex-wrap gap-1">
                        <span
                            v-for="tag in movie.tags.slice(0, 3)"
                            :key="tag.id"
                            class="rounded bg-zinc-800/80 px-2 py-0.5 text-xs text-zinc-300"
                        >
                            {{ tag.name }}
                        </span>
                    </div>
                </div>
            </div>

            <div v-if="movie.is_upcoming" class="absolute right-2 top-2 rounded bg-red-600 px-2 py-1 text-xs font-semibold text-white">
                Coming Soon
            </div>
        </Link>

        <!-- Admin Actions -->
        <div v-if="isAdmin" class="mt-2 flex gap-2">
            <Button
                v-if="isPublished"
                @click="handleUnpublish"
                variant="outline"
                size="sm"
                class="flex-1"
            >
                <XCircle class="size-3" />
                Unpublish
            </Button>
            <Button
                v-else
                @click="handlePublish"
                variant="default"
                size="sm"
                class="flex-1"
            >
                <CheckCircle class="size-3" />
                Publish
            </Button>
            <Button
                @click="handleArchive"
                variant="outline"
                size="sm"
                class="flex-1"
            >
                <Archive class="size-3" />
                Archive
            </Button>
        </div>
    </div>
</template>
