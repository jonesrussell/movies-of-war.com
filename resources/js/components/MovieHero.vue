<script setup lang="ts">
import type { Movie } from '@/types';

import { Link } from '@inertiajs/vue3';
import { Info, Play } from 'lucide-vue-next';

import PublicContainer from '@/components/public/PublicContainer.vue';

interface Props {
    movie: Movie;
    title?: string;
    subtitle?: string;
}

const props = defineProps<Props>();

const posterImage =
    props.movie.poster_url || '/images/placeholders/poster-placeholder.png';
</script>

<template>
    <section class="relative overflow-hidden bg-zinc-950">
        <div class="absolute inset-0">
            <img
                :src="posterImage"
                :alt="movie.title"
                class="h-full w-full object-cover opacity-25 blur-xl"
                fetchpriority="high"
                decoding="async"
            />
            <div
                class="absolute inset-0 bg-gradient-to-r from-zinc-950 via-zinc-950/80 to-zinc-950/10"
            />
            <div
                class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-transparent to-zinc-950/10"
            />
            <div
                class="pointer-events-none absolute inset-0 opacity-[0.06] [background-image:radial-gradient(circle_at_1px_1px,white_1px,transparent_0)] [background-size:18px_18px]"
            />
        </div>

        <PublicContainer as="div" class="relative py-14 sm:py-16 lg:py-20">
            <div class="grid gap-8 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <img
                        :src="posterImage"
                        :alt="movie.title"
                        class="w-full rounded-2xl shadow-2xl ring-1 ring-white/10"
                        fetchpriority="high"
                        decoding="async"
                    />
                </div>

                <div class="flex flex-col justify-center lg:col-span-8 lg:pr-8">
                    <div
                        v-if="subtitle"
                        class="mb-2 text-xs font-semibold tracking-[0.2em] text-red-500 uppercase"
                    >
                        {{ subtitle }}
                    </div>

                    <h1
                        class="mb-4 text-balance text-4xl font-semibold tracking-tight text-white sm:text-5xl lg:text-6xl"
                    >
                        {{ movie.title }}
                    </h1>

                    <div
                        class="mb-6 flex flex-wrap items-center gap-4 text-sm text-zinc-300"
                    >
                        <span class="font-semibold">{{
                            movie.release_year
                        }}</span>
                        <span v-if="movie.runtime"
                            >{{ movie.runtime }} min</span
                        >
                        <span
                            v-if="movie.conflict"
                            class="rounded-full bg-zinc-900 px-3 py-1 text-xs font-semibold ring-1 ring-zinc-800/70"
                        >
                            {{ movie.conflict }}
                        </span>
                    </div>

                    <p class="mb-8 text-lg leading-relaxed text-zinc-300">
                        {{ movie.synopsis }}
                    </p>

                    <div
                        v-if="movie.tags && movie.tags.length > 0"
                        class="mb-8 flex flex-wrap gap-2"
                    >
                        <span
                            v-for="tag in movie.tags"
                            :key="tag.id"
                            class="rounded-full bg-zinc-900 px-3 py-1 text-sm text-zinc-300 ring-1 ring-zinc-800/70"
                        >
                            {{ tag.name }}
                        </span>
                    </div>

                    <div class="flex flex-wrap gap-4">
                        <Link
                            :href="`/movies/${movie.slug}`"
                            class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                        >
                            <Info class="size-5" />
                            More Details
                        </Link>

                        <a
                            v-if="movie.trailer_url"
                            :href="movie.trailer_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 rounded-xl bg-zinc-900 px-5 py-3 text-sm font-semibold text-white ring-1 ring-zinc-800/70 transition-colors hover:bg-zinc-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                        >
                            <Play class="size-5" />
                            Watch Trailer
                        </a>
                    </div>
                </div>
            </div>
        </PublicContainer>
    </section>
</template>
