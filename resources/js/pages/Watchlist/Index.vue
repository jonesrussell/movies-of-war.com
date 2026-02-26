<script setup lang="ts">
import type { Movie } from '@/types';

import { Head, Link } from '@inertiajs/vue3';
import { Bookmark } from 'lucide-vue-next';

import MovieCard from '@/components/MovieCard.vue';
import CoordinateGrid from '@/components/primitives/CoordinateGrid.vue';
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
                        <Bookmark class="size-6 text-[--intel-accent]" />
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
                    class="relative overflow-hidden rounded-md border border-[--intel-border] bg-[--intel-bg-surface] p-10 text-center"
                >
                    <CoordinateGrid :opacity="0.06" />
                    <div class="relative">
                        <img
                            src="/images/illustrations/watchlist-placeholder.png"
                            alt="Empty watchlist"
                            class="mx-auto mb-6 h-32 w-32 opacity-60"
                            loading="lazy"
                            decoding="async"
                        />
                        <h2
                            class="font-[family-name:var(--font-mono-display)] text-2xl font-semibold tracking-tight text-balance text-[--intel-text-primary]"
                        >
                            NO TARGETS ACQUIRED
                        </h2>
                        <p
                            class="mx-auto mt-3 max-w-lg text-[--intel-text-muted]"
                        >
                            Your watchlist is empty. Browse the database to add
                            films.
                        </p>
                        <div class="mt-7 flex justify-center">
                            <Button
                                as-child
                                class="bg-[--intel-accent] hover:bg-[--intel-accent-dim]"
                            >
                                <Link href="/movies">BROWSE DATABASE</Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </PublicContainer>
        </PublicSection>
    </PublicLayout>
</template>
