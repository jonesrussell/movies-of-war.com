<script setup lang="ts">
import type { Movie, Review, UserReviewSummary } from '@/types';

import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import FeaturedMovie from '@/components/FeaturedMovie.vue';
import JsonLdScript from '@/components/JsonLdScript.vue';
import MovieCard from '@/components/MovieCard.vue';
import MovieHero from '@/components/MovieHero.vue';
import CoordinateGrid from '@/components/primitives/CoordinateGrid.vue';
import ScanLine from '@/components/primitives/ScanLine.vue';
import MovieGrid from '@/components/public/MovieGrid.vue';
import PublicContainer from '@/components/public/PublicContainer.vue';
import PublicSection from '@/components/public/PublicSection.vue';
import SectionHeader from '@/components/public/SectionHeader.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';

interface Props {
    heroMovie?: Movie;
    pickOfWeekMovie?: Movie;
    pickOfWeekReview?: Pick<Review, 'rating' | 'content_excerpt'> | null;
    pickOfWeekUserReview?: UserReviewSummary | null;
    latestMovies: Movie[];
    upcomingMovies: Movie[];
}

defineProps<Props>();

const page = usePage();
const appUrl = computed(() => (page.props.appUrl as string) ?? '');
const canonicalUrl = computed(() => (appUrl.value ? `${appUrl.value}/` : ''));
const defaultOgImage = computed(() =>
    appUrl.value ? `${appUrl.value}/images/branding/hero-bg.png` : '',
);
const defaultDescription =
    'Discover and explore a curated collection of war films from throughout cinema history.';
const jsonLdWebSite = computed(() =>
    JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'WebSite',
        name: 'Movies of War',
        url: canonicalUrl.value,
        description: defaultDescription,
    }),
);
</script>

<template>
    <PublicLayout>
        <Head title="Curated War Films Database">
            <meta
                head-key="description"
                name="description"
                :content="defaultDescription"
            />
            <link head-key="canonical" rel="canonical" :href="canonicalUrl" />
            <meta property="og:type" content="website" />
            <meta property="og:title" content="Curated War Films Database" />
            <meta property="og:description" :content="defaultDescription" />
            <meta property="og:image" :content="defaultOgImage" />
            <meta property="og:url" :content="canonicalUrl" />
            <meta property="og:site_name" content="Movies of War" />
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:title" content="Curated War Films Database" />
            <meta name="twitter:description" :content="defaultDescription" />
            <meta name="twitter:image" :content="defaultOgImage" />
        </Head>

        <JsonLdScript :content="jsonLdWebSite" />

        <!-- Pick of the Week first (curator's featured review, hero style) -->
        <MovieHero
            v-if="pickOfWeekMovie"
            :movie="pickOfWeekMovie"
            subtitle="Pick of the Week"
            :review="pickOfWeekReview ?? null"
            :user-review="pickOfWeekUserReview ?? null"
        />

        <!-- Featured Upcoming Release (card style) -->
        <PublicSection v-if="heroMovie" spacing="md">
            <PublicContainer class="flex flex-col gap-10 lg:gap-18">
                <div class="mx-auto w-full max-w-5xl">
                    <FeaturedMovie
                        :movie="heroMovie"
                        title="Featured Upcoming Release"
                    />
                </div>
            </PublicContainer>
        </PublicSection>

        <PublicSection v-else spacing="lg" class="relative overflow-hidden">
            <div class="absolute inset-0">
                <div
                    class="absolute inset-0 bg-gradient-to-b from-[--intel-bg-base] via-[--intel-bg-base] to-[--intel-bg-base]"
                />
                <CoordinateGrid density="normal" :opacity="0.06" />
            </div>

            <PublicContainer class="relative">
                <div class="mx-auto max-w-3xl text-center">
                    <p
                        class="font-[family-name:var(--font-mono-display)] text-xs font-semibold tracking-[0.2em] text-blue-500 uppercase"
                    >
                        Awaiting Priority Briefing
                    </p>
                    <h1
                        class="mt-4 font-semibold tracking-tight text-balance text-[--intel-text-primary]"
                        style="font-size: clamp(2rem, 4vw + 1rem, 3.75rem)"
                    >
                        Movies of War
                    </h1>
                    <p
                        class="mt-5 text-lg leading-relaxed text-[--intel-text-body]"
                    >
                        A curated database of war films spanning WWI, WWII,
                        Vietnam, and modern conflicts—built for browsing,
                        collecting, and discovering.
                    </p>
                    <div class="mt-8 flex flex-wrap justify-center gap-3">
                        <Link
                            href="/movies"
                            class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-[--intel-text-primary] transition-colors hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                        >
                            Browse movies
                        </Link>
                        <Link
                            href="/movies?conflict=WWII"
                            class="inline-flex items-center justify-center rounded-xl bg-[--intel-bg-surface] px-5 py-3 text-sm font-semibold text-[--intel-text-primary] ring-1 ring-[--intel-border] transition-colors hover:bg-[--intel-bg-elevated] focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                        >
                            Start with WWII
                        </Link>
                    </div>
                </div>
            </PublicContainer>
        </PublicSection>

        <PublicSection spacing="md">
            <PublicContainer class="flex flex-col gap-10 lg:gap-18">
                <div class="flex flex-col gap-6">
                    <SectionHeader
                        title="Latest Releases"
                        description="Fresh additions to the collection—newly curated, newly discovered, or newly published."
                    >
                        <template #action>
                            <Link
                                href="/movies"
                                class="text-sm font-semibold text-blue-500 transition-colors hover:text-blue-400"
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

                <div
                    v-if="upcomingMovies.length > 0"
                    class="flex flex-col gap-6"
                >
                    <SectionHeader
                        title="Upcoming"
                        description="War films on the horizon—announced, in production, or releasing soon."
                    >
                        <template #action>
                            <Link
                                href="/movies?upcoming=1"
                                class="text-sm font-semibold text-blue-500 transition-colors hover:text-blue-400"
                            >
                                View all →
                            </Link>
                        </template>
                    </SectionHeader>

                    <MovieGrid>
                        <MovieCard
                            v-for="movie in upcomingMovies"
                            :key="movie.id"
                            :movie="movie"
                        />
                    </MovieGrid>
                </div>
            </PublicContainer>
        </PublicSection>

        <!-- Intel Summary -->
        <PublicSection spacing="md">
            <PublicContainer>
                <ScanLine class="mb-8" />
                <div
                    class="flex flex-col items-center gap-6 sm:flex-row sm:justify-center"
                >
                    <div class="text-center">
                        <p
                            class="font-[family-name:var(--font-mono-display)] text-2xl font-bold text-[--intel-text-primary]"
                        >
                            {{ latestMovies.length + upcomingMovies.length }}+
                        </p>
                        <p
                            class="font-[family-name:var(--font-mono-display)] text-xs tracking-wider text-[--intel-text-muted] uppercase"
                        >
                            Films Indexed
                        </p>
                    </div>
                    <Link
                        href="/movies"
                        class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-[--intel-text-primary] transition-colors hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                    >
                        Access Full Database
                    </Link>
                </div>
            </PublicContainer>
        </PublicSection>
    </PublicLayout>
</template>
