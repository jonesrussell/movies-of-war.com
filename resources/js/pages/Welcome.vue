<script setup lang="ts">
import type { Movie } from '@/types';

import { Head, Link } from '@inertiajs/vue3';

import FeaturedMovie from '@/components/FeaturedMovie.vue';
import MovieCard from '@/components/MovieCard.vue';
import MovieHero from '@/components/MovieHero.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';

interface Props {
    canRegister: boolean;
    heroMovie?: Movie;
    pickOfWeekMovie?: Movie;
    latestMovies: Movie[];
}

defineProps<Props>();
</script>

<template>
    <PublicLayout :can-register="canRegister">

        <Head title="Movies of War - Curated War Films Database" />

        <MovieHero v-if="heroMovie" :movie="heroMovie" subtitle="Featured Upcoming Release" />

        <div v-else class="bg-zinc-900 py-20">
            <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                <h1 class="text-5xl font-bold text-white">Movies of War</h1>
                <p class="mt-4 text-xl text-zinc-400">
                    A curated database of war films, documentaries, and related media
                </p>
            </div>
        </div>

        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div v-if="pickOfWeekMovie" class="mx-auto mb-12 max-w-4xl">
                <FeaturedMovie :movie="pickOfWeekMovie" title="Pick of the Week" />
            </div>

            <div>
                <div class="mb-8 flex items-center justify-between">
                    <h2 class="text-3xl font-bold text-white">Latest Releases</h2>
                    <Link href="/movies" class="text-red-500 transition-colors hover:text-red-400">
                        View All →
                    </Link>
                </div>

                <div class="grid grid-cols-2 gap-6 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
                    <MovieCard v-for="movie in latestMovies" :key="movie.id" :movie="movie" />
                </div>
            </div>

            <div class="mt-16 rounded-lg bg-zinc-900 p-8 text-center">
                <h3 class="mb-4 text-2xl font-bold text-white">
                    Explore Our Curated Collection
                </h3>
                <p class="mb-6 text-zinc-400">
                    Discover 30+ carefully selected war films spanning WWI, WWII, Vietnam, and modern conflicts
                </p>
                <Link href="/movies"
                    class="inline-block rounded-lg bg-red-600 px-6 py-3 font-semibold text-white transition-colors hover:bg-red-700">
                    Browse All Movies
                </Link>
            </div>
        </div>
    </PublicLayout>
</template>
