<script setup lang="ts">
import type { Movie } from '@/types';

import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Calendar,
    Check,
    Clock,
    Film,
    MapPin,
    Play,
    Plus,
} from 'lucide-vue-next';

import MovieCard from '@/components/MovieCard.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { User } from '@/types/models';

interface Props {
    movie: Movie;
    relatedMovies: Movie[];
}

const props = defineProps<Props>();
const page = usePage();

const auth = page.props.auth as { user?: User };

const toggleWatchlist = () => {
    if (!auth.user) {
        router.visit('/login');
        return;
    }

    if (props.movie.is_watchlisted) {
        router.delete(`/watchlist/${props.movie.id}`, {
            preserveScroll: true,
        });
    } else {
        router.post(
            `/watchlist/${props.movie.id}`,
            {},
            {
                preserveScroll: true,
            },
        );
    }
};

const posterImage =
    props.movie.poster_url || '/images/placeholders/poster-placeholder.png';
</script>

<template>
    <PublicLayout>
        <Head :title="`${movie.title} - Movies of War`" />

        <div class="relative overflow-hidden bg-zinc-950">
            <div class="absolute inset-0">
                <img
                    :src="posterImage"
                    :alt="movie.title"
                    class="h-full w-full object-cover opacity-20 blur-2xl"
                />
                <div
                    class="absolute inset-0 bg-gradient-to-b from-zinc-950 via-zinc-950/80 to-zinc-950"
                />
            </div>

            <div class="relative mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <div class="mb-4">
                    <Link
                        href="/movies"
                        class="text-zinc-400 transition-colors hover:text-white"
                    >
                        ← Back to Movies
                    </Link>
                </div>

                <div class="grid gap-8 lg:grid-cols-12">
                    <div class="lg:col-span-4">
                        <img
                            :src="posterImage"
                            :alt="movie.title"
                            class="w-full rounded-lg shadow-2xl"
                        />

                        <button
                            v-if="auth.user"
                            @click="toggleWatchlist"
                            :class="[
                                'mt-4 flex w-full items-center justify-center gap-2 rounded-lg px-6 py-3 font-semibold transition-colors',
                                movie.is_watchlisted
                                    ? 'bg-zinc-800 text-white hover:bg-zinc-700'
                                    : 'bg-red-600 text-white hover:bg-red-700',
                            ]"
                        >
                            <component
                                :is="movie.is_watchlisted ? Check : Plus"
                                class="size-5"
                            />
                            {{
                                movie.is_watchlisted
                                    ? 'In Watchlist'
                                    : 'Add to Watchlist'
                            }}
                        </button>
                    </div>

                    <div class="flex flex-col lg:col-span-8">
                        <div>
                            <div
                                v-if="movie.is_upcoming"
                                class="mb-2 inline-block rounded bg-red-600 px-3 py-1 text-sm font-semibold text-white"
                            >
                                Coming Soon
                            </div>

                            <h1 class="mb-4 text-5xl font-bold text-white">
                                {{ movie.title }}
                            </h1>

                            <div
                                class="mb-6 flex flex-wrap items-center gap-6 text-zinc-300"
                            >
                                <div class="flex items-center gap-2">
                                    <Calendar class="size-5" />
                                    <span>{{ movie.release_year }}</span>
                                </div>

                                <div
                                    v-if="movie.runtime"
                                    class="flex items-center gap-2"
                                >
                                    <Clock class="size-5" />
                                    <span>{{ movie.runtime }} min</span>
                                </div>

                                <div
                                    v-if="movie.country"
                                    class="flex items-center gap-2"
                                >
                                    <MapPin class="size-5" />
                                    <span>{{ movie.country }}</span>
                                </div>

                                <div
                                    v-if="movie.conflict"
                                    class="flex items-center gap-2"
                                >
                                    <Film class="size-5" />
                                    <span>{{ movie.conflict }}</span>
                                </div>
                            </div>

                            <div
                                v-if="movie.tags && movie.tags.length > 0"
                                class="mb-6 flex flex-wrap gap-2"
                            >
                                <Link
                                    v-for="tag in movie.tags"
                                    :key="tag.id"
                                    :href="`/movies?tag=${tag.slug}`"
                                    class="rounded-full bg-zinc-800 px-4 py-1.5 text-sm text-zinc-300 transition-colors hover:bg-zinc-700"
                                >
                                    {{ tag.name }}
                                </Link>
                            </div>

                            <div class="mb-8">
                                <h2 class="mb-3 text-2xl font-bold text-white">
                                    Synopsis
                                </h2>
                                <p class="leading-relaxed text-zinc-300">
                                    {{ movie.synopsis }}
                                </p>
                            </div>

                            <div v-if="movie.trailer_url" class="mb-8">
                                <a
                                    :href="movie.trailer_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-6 py-3 font-semibold text-white transition-colors hover:bg-red-700"
                                >
                                    <Play class="size-5" />
                                    Watch Trailer
                                </a>
                            </div>

                            <div
                                v-if="movie.imdb_id"
                                class="text-sm text-zinc-500"
                            >
                                <a
                                    :href="`https://www.imdb.com/title/${movie.imdb_id}/`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="transition-colors hover:text-zinc-400"
                                >
                                    View on IMDb
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="relatedMovies.length > 0"
            class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8"
        >
            <h2 class="mb-8 text-3xl font-bold text-white">Related Films</h2>
            <div
                class="grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6"
            >
                <MovieCard
                    v-for="movie in relatedMovies"
                    :key="movie.id"
                    :movie="movie"
                />
            </div>
        </div>
    </PublicLayout>
</template>
