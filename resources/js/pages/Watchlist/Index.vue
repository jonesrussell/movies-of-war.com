<script setup lang="ts">
import type { Movie } from '@/types';

import { Head, Link } from '@inertiajs/vue3';
import { Bookmark } from 'lucide-vue-next';

import MovieCard from '@/components/MovieCard.vue';
import MovieGrid from '@/components/public/MovieGrid.vue';
import PublicContainer from '@/components/public/PublicContainer.vue';
import PublicSection from '@/components/public/PublicSection.vue';
import SectionHeader from '@/components/public/SectionHeader.vue';
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/PublicLayout.vue';

interface Props {
    movies: Movie[];
}

defineProps<Props>();
</script>

<template>
    <PublicLayout>
        <Head title="My Watchlist - Movies of War" />

        <PublicSection spacing="md">
            <PublicContainer class="flex flex-col gap-8">
                <SectionHeader
                    title="My Watchlist"
                    :description="`${movies.length} ${
                        movies.length === 1 ? 'film' : 'films'
                    } saved`"
                >
                    <template #action>
                        <Bookmark class="size-6 text-red-500" />
                    </template>
                </SectionHeader>

                <div v-if="movies.length > 0">
                    <MovieGrid>
                        <MovieCard
                            v-for="movie in movies"
                            :key="movie.id"
                            :movie="movie"
                        />
                    </MovieGrid>
                </div>

                <div
                    v-else
                    class="relative overflow-hidden rounded-2xl bg-zinc-950 p-10 text-center ring-1 ring-zinc-800/70"
                >
                    <div
                        class="pointer-events-none absolute inset-0 opacity-[0.10] [background-image:radial-gradient(circle_at_1px_1px,white_1px,transparent_0)] [background-size:22px_22px]"
                    />
                    <div class="relative">
                        <img
                            src="/images/illustrations/watchlist-placeholder.png"
                            alt="Empty watchlist"
                            class="mx-auto mb-6 h-32 w-32 opacity-60"
                            loading="lazy"
                            decoding="async"
                        />
                        <h2
                            class="text-balance text-2xl font-semibold tracking-tight text-white"
                        >
                            Your watchlist is empty
                        </h2>
                        <p class="mx-auto mt-3 max-w-lg text-zinc-400">
                            Save films you want to revisit later—then come back
                            when you’re ready for a deep dive.
                        </p>
                        <div class="mt-7 flex justify-center">
                            <Button as-child>
                                <Link href="/movies">Browse movies</Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </PublicContainer>
        </PublicSection>
    </PublicLayout>
</template>
