<script setup lang="ts">
import type { AppPageProps } from '@/types';
import type { Movie } from '@/types';

import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Check, Play, Plus } from 'lucide-vue-next';
import { computed } from 'vue';

import MovieCard from '@/components/MovieCard.vue';
import MovieFacts from '@/components/public/MovieFacts.vue';
import MovieGrid from '@/components/public/MovieGrid.vue';
import PublicContainer from '@/components/public/PublicContainer.vue';
import PublicSection from '@/components/public/PublicSection.vue';
import SectionHeader from '@/components/public/SectionHeader.vue';
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/PublicLayout.vue';

interface Props {
    movie: Movie;
    relatedMovies: Movie[];
}

interface PageProps extends AppPageProps {
    movie: Movie;
    relatedMovies: Movie[];
}

const props = defineProps<Props>();
const page = usePage<PageProps>();

const auth = page.props.auth;

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

const siteUrl = 'https://movies-of-war.com';

const posterImage = computed(
    () =>
        props.movie.poster_url || '/images/placeholders/poster-placeholder.png',
);

const pageUrl = computed(() => `${siteUrl}/movies/${props.movie.slug}`);

const ogImage = computed(() =>
    props.movie.poster_url?.startsWith('http')
        ? props.movie.poster_url
        : `${siteUrl}${props.movie.poster_url || '/images/placeholders/poster-placeholder.png'}`,
);

const ogDescription = computed(() => {
    const synopsis = props.movie.synopsis || '';
    return synopsis.length > 200 ? synopsis.slice(0, 200) + '...' : synopsis;
});
</script>

<template>
    <PublicLayout>
        <Head :title="`${movie.title} (${movie.release_year}) - Movies of War`">
            <meta name="description" :content="ogDescription" />
            <meta property="og:type" content="video.movie" />
            <meta
                property="og:title"
                :content="`${movie.title} (${movie.release_year})`"
            />
            <meta property="og:description" :content="ogDescription" />
            <meta property="og:image" :content="ogImage" />
            <meta property="og:url" :content="pageUrl" />
            <meta property="og:site_name" content="Movies of War" />
            <meta name="twitter:card" content="summary_large_image" />
            <meta
                name="twitter:title"
                :content="`${movie.title} (${movie.release_year})`"
            />
            <meta name="twitter:description" :content="ogDescription" />
            <meta name="twitter:image" :content="ogImage" />
        </Head>

        <div class="relative overflow-hidden bg-zinc-950">
            <div class="absolute inset-0">
                <img
                    :src="posterImage"
                    :alt="movie.title"
                    class="h-full w-full object-cover opacity-25 blur-2xl"
                    fetchpriority="high"
                    decoding="async"
                />
                <div
                    class="absolute inset-0 bg-gradient-to-b from-zinc-950 via-zinc-950/80 to-zinc-950"
                />
                <div
                    class="pointer-events-none absolute inset-0 [background-image:radial-gradient(circle_at_1px_1px,white_1px,transparent_0)] [background-size:18px_18px] opacity-[0.06]"
                />
            </div>

            <PublicContainer class="relative py-12 sm:py-14 lg:py-16">
                <div class="mb-6">
                    <Link
                        href="/movies"
                        class="text-sm text-zinc-400 transition-colors hover:text-white"
                    >
                        ← Back to Movies
                    </Link>
                </div>

                <div
                    class="movie-detail-grid grid grid-cols-1 gap-8"
                    style="container-type: inline-size"
                >
                    <div class="movie-detail-poster">
                        <img
                            :src="posterImage"
                            :alt="movie.title"
                            class="w-full rounded-2xl shadow-2xl ring-1 ring-white/10"
                            fetchpriority="high"
                            decoding="async"
                        />

                        <div class="mt-4">
                            <Button
                                v-if="auth.user"
                                class="w-full"
                                :variant="
                                    movie.is_watchlisted ? 'outline' : 'default'
                                "
                                @click="toggleWatchlist"
                            >
                                <component
                                    :is="movie.is_watchlisted ? Check : Plus"
                                    class="size-4"
                                />
                                {{
                                    movie.is_watchlisted
                                        ? 'In Watchlist'
                                        : 'Add to Watchlist'
                                }}
                            </Button>
                            <Button
                                v-else
                                class="w-full"
                                variant="default"
                                @click="toggleWatchlist"
                            >
                                <Plus class="size-4" />
                                Add to Watchlist
                            </Button>
                        </div>
                    </div>

                    <div class="movie-detail-content flex flex-col">
                        <div>
                            <div
                                v-if="movie.is_upcoming"
                                class="mb-3 inline-flex rounded-full bg-red-600/90 px-3 py-1 text-xs font-semibold text-white"
                            >
                                Coming Soon
                            </div>

                            <h1
                                class="mb-5 font-semibold tracking-tight text-balance text-white"
                                style="
                                    font-size: clamp(2rem, 3.5vw + 1rem, 3rem);
                                "
                            >
                                {{ movie.title }}
                            </h1>

                            <MovieFacts class="mb-8" :movie="movie" />

                            <div
                                v-if="movie.tags && movie.tags.length > 0"
                                class="mb-6 flex flex-wrap gap-2"
                            >
                                <Link
                                    v-for="tag in movie.tags"
                                    :key="tag.id"
                                    :href="`/movies?tag=${tag.slug}`"
                                    class="rounded-full bg-zinc-950 px-4 py-1.5 text-sm text-zinc-200 ring-1 ring-zinc-800/70 transition-colors hover:bg-zinc-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                                >
                                    {{ tag.name }}
                                </Link>
                            </div>

                            <div class="mb-8">
                                <h2 class="text-xl font-semibold text-white">
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
                                    class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
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
            </PublicContainer>
        </div>

        <PublicSection v-if="relatedMovies.length > 0" spacing="md">
            <PublicContainer class="flex flex-col gap-6">
                <SectionHeader title="Related Films" />
                <MovieGrid>
                    <MovieCard
                        v-for="movie in relatedMovies"
                        :key="movie.id"
                        :movie="movie"
                    />
                </MovieGrid>
            </PublicContainer>
        </PublicSection>
    </PublicLayout>
</template>
