<script setup lang="ts">
import type { Movie } from '@/types';

import { Head, Link } from '@inertiajs/vue3';

import FeaturedMovie from '@/components/FeaturedMovie.vue';
import MovieCard from '@/components/MovieCard.vue';
import MovieHero from '@/components/MovieHero.vue';
import MovieGrid from '@/components/public/MovieGrid.vue';
import PublicContainer from '@/components/public/PublicContainer.vue';
import PublicSection from '@/components/public/PublicSection.vue';
import SectionHeader from '@/components/public/SectionHeader.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';

interface Props {
    heroMovie?: Movie;
    pickOfWeekMovie?: Movie;
    latestMovies: Movie[];
}

defineProps<Props>();
</script>

<template>
    <PublicLayout>
        <Head title="Movies of War - Curated War Films Database" />

        <MovieHero
            v-if="heroMovie"
            :movie="heroMovie"
            subtitle="Featured Upcoming Release"
        />

        <PublicSection v-else spacing="lg" class="relative overflow-hidden">
            <div class="absolute inset-0">
                <div
                    class="absolute inset-0 bg-gradient-to-b from-zinc-950 via-zinc-950 to-zinc-950"
                />
                <div
                    class="pointer-events-none absolute inset-0 [background-image:radial-gradient(circle_at_1px_1px,white_1px,transparent_0)] [background-size:18px_18px] opacity-[0.06]"
                />
            </div>

            <PublicContainer class="relative">
                <div class="mx-auto max-w-3xl text-center">
                    <p
                        class="text-xs font-semibold tracking-[0.2em] text-red-500 uppercase"
                    >
                        Curated war cinema
                    </p>
                    <h1
                        class="mt-4 text-4xl font-semibold tracking-tight text-balance text-white sm:text-5xl lg:text-6xl"
                    >
                        Movies of War
                    </h1>
                    <p class="mt-5 text-lg leading-relaxed text-zinc-300">
                        A curated database of war films spanning WWI, WWII,
                        Vietnam, and modern conflicts—built for browsing,
                        collecting, and discovering.
                    </p>
                    <div class="mt-8 flex flex-wrap justify-center gap-3">
                        <Link
                            href="/movies"
                            class="inline-flex items-center justify-center rounded-xl bg-red-600 px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                        >
                            Browse movies
                        </Link>
                        <Link
                            href="/movies?conflict=WWII"
                            class="inline-flex items-center justify-center rounded-xl bg-zinc-900 px-5 py-3 text-sm font-semibold text-white ring-1 ring-zinc-800/70 transition-colors hover:bg-zinc-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                        >
                            Start with WWII
                        </Link>
                    </div>
                </div>
            </PublicContainer>
        </PublicSection>

        <PublicSection spacing="md">
            <PublicContainer class="flex flex-col gap-10 lg:gap-18">
                <div v-if="pickOfWeekMovie" class="mx-auto w-full max-w-5xl">
                    <FeaturedMovie
                        :movie="pickOfWeekMovie"
                        title="Pick of the Week"
                    />
                </div>

                <div class="flex flex-col gap-6">
                    <SectionHeader
                        title="Latest Releases"
                        description="Fresh additions to the collection—newly curated, newly discovered, or newly published."
                    >
                        <template #action>
                            <Link
                                href="/movies"
                                class="text-sm font-semibold text-red-500 transition-colors hover:text-red-400"
                            >
                                View all →
                            </Link>
                        </template>
                    </SectionHeader>

                    <MovieGrid>
                        <MovieCard
                            v-for="movie in latestMovies"
                            :key="movie.id"
                            :movie="movie"
                        />
                    </MovieGrid>
                </div>
            </PublicContainer>
        </PublicSection>

        <PublicSection spacing="md">
            <PublicContainer>
                <div
                    class="relative overflow-hidden rounded-2xl bg-zinc-950 p-8 text-center ring-1 ring-zinc-800/70 sm:p-10"
                >
                    <div
                        class="pointer-events-none absolute inset-0 [background-image:radial-gradient(circle_at_1px_1px,white_1px,transparent_0)] [background-size:22px_22px] opacity-[0.10]"
                    />
                    <div class="relative">
                        <h2
                            class="text-2xl font-semibold tracking-tight text-balance text-white sm:text-3xl"
                        >
                            Explore the curated collection
                        </h2>
                        <p class="mx-auto mt-3 max-w-prose text-zinc-400">
                            Filter by conflict, country, year, and tags—then
                            build your watchlist as you go.
                        </p>
                        <div class="mt-7 flex justify-center">
                            <Link
                                href="/movies"
                                class="inline-flex items-center justify-center rounded-xl bg-red-600 px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                            >
                                Browse all movies
                            </Link>
                        </div>
                    </div>
                </div>
            </PublicContainer>
        </PublicSection>
    </PublicLayout>
</template>
