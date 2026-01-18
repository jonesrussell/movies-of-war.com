<script setup lang="ts">
import type { AppPageProps, Movie } from '@/types';

import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Check, Play, Plus } from 'lucide-vue-next';
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';

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

// Entrance animation state
const isVisible = ref(false);
const relatedVisible = ref(false);
const relatedSection = ref<InstanceType<typeof PublicSection> | null>(null);
let observer: IntersectionObserver | null = null;

onMounted(() => {
    // Trigger entrance animations
    requestAnimationFrame(() => {
        isVisible.value = true;
    });

    // Set up Intersection Observer for related films section
    // Wait for nextTick to ensure component is fully mounted
    void nextTick(() => {
        const element =
            (relatedSection.value?.$el as HTMLElement | undefined) ?? null;
        if (element) {
            observer = new IntersectionObserver(
                (entries) => {
                    const entry = entries[0];
                    if (entry?.isIntersecting) {
                        relatedVisible.value = true;
                        observer?.disconnect();
                    }
                },
                { threshold: 0.1 },
            );
            observer.observe(element);
        }
    });
});

onUnmounted(() => {
    observer?.disconnect();
});

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
            <!-- Background layers -->
            <div class="absolute inset-0">
                <!-- Blurred poster background with scale animation -->
                <img
                    :src="posterImage"
                    :alt="movie.title"
                    class="h-full w-full scale-110 object-cover opacity-25 blur-2xl transition-transform duration-[2000ms] [transition-timing-function:var(--ease-smooth-out)]"
                    :class="isVisible ? 'scale-100' : 'scale-110'"
                    fetchpriority="high"
                    decoding="async"
                />
                <!-- Multiple gradient layers for depth -->
                <div
                    class="absolute inset-0 bg-gradient-to-b from-zinc-950 via-zinc-950/80 to-zinc-950"
                />
                <div class="absolute inset-0 bg-zinc-950/20" />
                <!-- Film texture pattern -->
                <div
                    class="pointer-events-none absolute inset-0 [background-image:radial-gradient(circle_at_1px_1px,white_1px,transparent_0)] [background-size:18px_18px] opacity-[0.07]"
                />
            </div>

            <PublicContainer class="relative py-12 sm:py-14 lg:py-16">
                <!-- Back link with hover animation -->
                <div class="mb-6">
                    <Link
                        href="/movies"
                        class="group inline-flex items-center gap-2 text-sm text-zinc-400 transition-colors hover:text-white focus:outline-none focus-visible:text-white"
                    >
                        <ArrowLeft
                            class="size-4 transition-transform duration-200 group-hover:-translate-x-1"
                        />
                        Back to Movies
                    </Link>
                </div>

                <div
                    class="movie-detail-grid grid grid-cols-1 gap-8"
                    style="container-type: inline-size"
                >
                    <!-- Poster with entrance animation and hover effects -->
                    <div
                        class="movie-detail-poster transform transition-all duration-700 [transition-timing-function:var(--ease-smooth-out)]"
                        :class="
                            isVisible
                                ? 'translate-y-0 opacity-100'
                                : 'translate-y-8 opacity-0'
                        "
                    >
                        <div
                            class="overflow-hidden rounded-2xl transition-all duration-300 hover:shadow-[0_25px_50px_-12px_rgba(0,0,0,0.5)]"
                        >
                            <img
                                :src="posterImage"
                                :alt="movie.title"
                                class="aspect-[2/3] w-full object-cover shadow-2xl ring-1 ring-white/10 transition-all duration-300 hover:scale-[1.02] hover:ring-white/20"
                                fetchpriority="high"
                                decoding="async"
                            />
                        </div>

                        <!-- Watchlist button with entrance animation -->
                        <div
                            class="mt-4 transform transition-all duration-700 [transition-timing-function:var(--ease-smooth-out)]"
                            :class="
                                isVisible
                                    ? 'translate-y-0 opacity-100'
                                    : 'translate-y-4 opacity-0'
                            "
                            :style="{ transitionDelay: '500ms' }"
                        >
                            <Button
                                v-if="auth.user"
                                class="w-full transition-all duration-200 hover:scale-[1.02]"
                                :class="
                                    movie.is_watchlisted
                                        ? 'hover:shadow-lg hover:shadow-red-600/10'
                                        : ''
                                "
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
                                class="w-full transition-all duration-200 hover:scale-[1.02]"
                                variant="default"
                                @click="toggleWatchlist"
                            >
                                <Plus class="size-4" />
                                Add to Watchlist
                            </Button>
                        </div>
                    </div>

                    <!-- Content section with staggered entrance animations -->
                    <div class="movie-detail-content flex flex-col">
                        <div>
                            <!-- Coming Soon badge -->
                            <div
                                v-if="movie.is_upcoming"
                                class="mb-3 inline-flex transform rounded-full bg-red-600/90 px-3 py-1 text-xs font-semibold text-white transition-all duration-700 [transition-timing-function:var(--ease-smooth-out)]"
                                :class="
                                    isVisible
                                        ? 'translate-y-0 opacity-100'
                                        : 'translate-y-4 opacity-0'
                                "
                                :style="{ transitionDelay: '100ms' }"
                            >
                                Coming Soon
                            </div>

                            <!-- Title with entrance animation -->
                            <h1
                                class="mb-5 transform font-semibold tracking-tight text-balance text-white transition-all duration-700 [transition-timing-function:var(--ease-smooth-out)]"
                                :class="
                                    isVisible
                                        ? 'translate-y-0 opacity-100'
                                        : 'translate-y-4 opacity-0'
                                "
                                :style="{ transitionDelay: '100ms' }"
                                style="
                                    font-size: clamp(2rem, 3.5vw + 1rem, 3rem);
                                "
                            >
                                {{ movie.title }}
                            </h1>

                            <!-- MovieFacts with entrance animation -->
                            <MovieFacts
                                class="mb-8 transform transition-all duration-700 [transition-timing-function:var(--ease-smooth-out)]"
                                :class="
                                    isVisible
                                        ? 'translate-y-0 opacity-100'
                                        : 'translate-y-4 opacity-0'
                                "
                                :style="{ transitionDelay: '200ms' }"
                                :movie="movie"
                            />

                            <!-- Tags with entrance animation and hover effects -->
                            <div
                                v-if="movie.tags && movie.tags.length > 0"
                                class="mb-6 flex transform flex-wrap gap-2 transition-all duration-700 [transition-timing-function:var(--ease-smooth-out)]"
                                :class="
                                    isVisible
                                        ? 'translate-y-0 opacity-100'
                                        : 'translate-y-4 opacity-0'
                                "
                                :style="{ transitionDelay: '300ms' }"
                            >
                                <Link
                                    v-for="tag in movie.tags"
                                    :key="tag.id"
                                    :href="`/movies?tag=${tag.slug}`"
                                    class="rounded-full bg-zinc-950 px-4 py-1.5 text-sm text-zinc-200 ring-1 ring-zinc-800/70 transition-all duration-200 hover:-translate-y-0.5 hover:bg-zinc-800 hover:text-white hover:ring-zinc-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                                >
                                    {{ tag.name }}
                                </Link>
                            </div>

                            <!-- Synopsis with entrance animation -->
                            <div
                                class="mb-8 transform transition-all duration-700 [transition-timing-function:var(--ease-smooth-out)]"
                                :class="
                                    isVisible
                                        ? 'translate-y-0 opacity-100'
                                        : 'translate-y-4 opacity-0'
                                "
                                :style="{ transitionDelay: '400ms' }"
                            >
                                <h2 class="text-xl font-semibold text-white">
                                    Synopsis
                                </h2>
                                <p class="leading-relaxed text-zinc-300">
                                    {{ movie.synopsis }}
                                </p>
                            </div>

                            <!-- Action buttons with entrance animation -->
                            <div
                                class="flex transform flex-wrap items-center gap-4 transition-all duration-700 [transition-timing-function:var(--ease-smooth-out)]"
                                :class="
                                    isVisible
                                        ? 'translate-y-0 opacity-100'
                                        : 'translate-y-4 opacity-0'
                                "
                                :style="{ transitionDelay: '500ms' }"
                            >
                                <!-- Trailer button with enhanced hover -->
                                <a
                                    v-if="movie.trailer_url"
                                    :href="movie.trailer_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-red-600/20 transition-all duration-200 hover:scale-105 hover:bg-red-700 hover:shadow-xl hover:shadow-red-600/30 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                                >
                                    <Play class="size-5" />
                                    Watch Trailer
                                </a>

                                <!-- IMDb link with underline animation -->
                                <a
                                    v-if="movie.imdb_id"
                                    :href="`https://www.imdb.com/title/${movie.imdb_id}/`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="group relative text-sm text-zinc-400 transition-colors hover:text-zinc-200"
                                >
                                    <span>View on IMDb</span>
                                    <span
                                        class="absolute bottom-0 left-0 h-px w-0 bg-zinc-400 transition-all duration-300 group-hover:w-full"
                                    />
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </PublicContainer>
        </div>

        <!-- Related Films section with scroll-triggered animation -->
        <PublicSection
            v-if="relatedMovies.length > 0"
            ref="relatedSection"
            spacing="md"
        >
            <PublicContainer class="flex flex-col gap-6">
                <SectionHeader
                    title="Related Films"
                    class="transform transition-all duration-700 [transition-timing-function:var(--ease-smooth-out)]"
                    :class="
                        relatedVisible
                            ? 'translate-y-0 opacity-100'
                            : 'translate-y-8 opacity-0'
                    "
                />
                <MovieGrid>
                    <MovieCard
                        v-for="(movie, index) in relatedMovies"
                        :key="movie.id"
                        :movie="movie"
                        class="transform transition-all duration-700 [transition-timing-function:var(--ease-smooth-out)]"
                        :class="
                            relatedVisible
                                ? 'translate-y-0 opacity-100'
                                : 'translate-y-8 opacity-0'
                        "
                        :style="{ transitionDelay: `${100 + index * 50}ms` }"
                    />
                </MovieGrid>
            </PublicContainer>
        </PublicSection>
    </PublicLayout>
</template>
