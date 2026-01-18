<script setup lang="ts">
import type { Movie } from '@/types/models';

import { Head, Link, router } from '@inertiajs/vue3';
import { Archive, CheckCircle, Edit, Play, Trash2, XCircle } from 'lucide-vue-next';

import MovieFacts from '@/components/public/MovieFacts.vue';
import { Button } from '@/components/ui/button';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';

interface Props {
    movie: Movie;
}

const props = defineProps<Props>();

function publishMovie() {
    router.post(
        `/movies/${props.movie.id}/publish`,
        {},
        {
            preserveScroll: true,
        },
    );
}

function unpublishMovie() {
    router.post(
        `/movies/${props.movie.id}/unpublish`,
        {},
        {
            preserveScroll: true,
        },
    );
}

function archiveMovie() {
    if (confirm(`Are you sure you want to archive "${props.movie.title}"?`)) {
        router.post(
            `/movies/${props.movie.id}/archive`,
            {},
            {
                preserveScroll: true,
            },
        );
    }
}

function deleteMovie() {
    if (
        confirm(
            `Are you sure you want to permanently delete "${props.movie.title}"? This action cannot be undone.`,
        )
    ) {
        router.delete(`/movies/${props.movie.id}`, {
            preserveScroll: true,
        });
    }
}

const posterImage =
    props.movie.poster_url || '/images/placeholders/poster-placeholder.png';
</script>

<template>
    <AppSidebarLayout>
        <Head :title="`${movie.title} - Admin`" />

        <div class="w-full px-4 py-8 sm:px-6 lg:px-8">
            <!-- Header with actions -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <Link
                        href="/dashboard/movies"
                        class="text-sm text-zinc-400 transition-colors hover:text-white"
                    >
                        ← Back to Movies
                    </Link>
                    <h1 class="mt-2 text-3xl font-bold text-white">
                        {{ movie.title }}
                    </h1>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="`/movies/${movie.id}/edit`"
                        class="inline-flex items-center justify-center rounded-lg bg-zinc-800 p-2 text-white transition-colors hover:bg-zinc-700"
                        title="Edit movie"
                    >
                        <Edit class="size-5" />
                    </Link>
                    <Button
                        v-if="movie.status !== 'published'"
                        @click="publishMovie"
                        class="bg-green-600 p-2 hover:bg-green-700"
                        title="Publish movie"
                    >
                        <CheckCircle class="size-5" />
                    </Button>
                    <Button
                        v-if="movie.status === 'published'"
                        @click="unpublishMovie"
                        variant="outline"
                        class="border-yellow-600 p-2 text-yellow-500 hover:bg-yellow-900/20"
                        title="Unpublish movie"
                    >
                        <XCircle class="size-5" />
                    </Button>
                    <Button
                        v-if="movie.status !== 'archived'"
                        @click="archiveMovie"
                        variant="outline"
                        class="border-zinc-600 p-2 text-zinc-400 hover:bg-zinc-800"
                        title="Archive movie"
                    >
                        <Archive class="size-5" />
                    </Button>
                    <Button
                        @click="deleteMovie"
                        variant="outline"
                        class="border-red-600 p-2 text-red-500 hover:bg-red-900/20"
                        title="Delete movie"
                    >
                        <Trash2 class="size-5" />
                    </Button>
                </div>
            </div>

            <!-- Status Badge -->
            <div class="mb-6">
                <span
                    :class="[
                        'inline-flex rounded-full px-3 py-1 text-xs font-semibold',
                        movie.status === 'published'
                            ? 'bg-green-900/50 text-green-300'
                            : movie.status === 'draft'
                              ? 'bg-yellow-900/50 text-yellow-300'
                              : 'bg-zinc-800 text-zinc-400',
                    ]"
                >
                    {{
                        movie.status === 'published'
                            ? 'Published'
                            : movie.status === 'draft'
                              ? 'Draft'
                              : 'Archived'
                    }}
                </span>
            </div>

            <!-- Movie Content -->
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <!-- Poster -->
                <div class="lg:col-span-1">
                    <img
                        :src="posterImage"
                        :alt="movie.title"
                        class="w-full rounded-2xl shadow-2xl ring-1 ring-white/10"
                        fetchpriority="high"
                        decoding="async"
                    />
                </div>

                <!-- Details -->
                <div class="space-y-6 lg:col-span-2">
                    <div>
                        <MovieFacts :movie="movie" />
                    </div>

                    <!-- Tags -->
                    <div
                        v-if="movie.tags && movie.tags.length > 0"
                        class="flex flex-wrap gap-2"
                    >
                        <span
                            v-for="tag in movie.tags"
                            :key="tag.id"
                            class="rounded-full bg-zinc-800 px-4 py-1.5 text-sm text-zinc-200"
                        >
                            {{ tag.name }}
                        </span>
                    </div>

                    <!-- Synopsis -->
                    <div>
                        <h2 class="mb-2 text-xl font-semibold text-white">
                            Synopsis
                        </h2>
                        <p class="leading-relaxed text-zinc-300">
                            {{ movie.synopsis }}
                        </p>
                    </div>

                    <!-- Trailer Link -->
                    <div v-if="movie.trailer_url">
                        <a
                            :href="movie.trailer_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-red-700"
                        >
                            <Play class="size-5" />
                            Watch Trailer
                        </a>
                    </div>

                    <!-- IMDb Link -->
                    <div v-if="movie.imdb_id" class="text-sm text-zinc-500">
                        <a
                            :href="`https://www.imdb.com/title/${movie.imdb_id}/`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="transition-colors hover:text-zinc-400"
                        >
                            View on IMDb
                        </a>
                    </div>

                    <!-- TMDB Info -->
                    <div
                        v-if="movie.tmdb_id"
                        class="rounded-lg bg-zinc-900 p-4 text-sm"
                    >
                        <div class="font-medium text-zinc-400">
                            TMDB Information
                        </div>
                        <div class="mt-1 text-zinc-500">
                            TMDB ID: {{ movie.tmdb_id }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppSidebarLayout>
</template>
