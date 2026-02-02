<script setup lang="ts">
import type { Movie } from '@/types';
import type { PaginationMeta, Review } from '@/types/models';

import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { ref } from 'vue';

import { Poster } from '@/components/primitives';
import PublicContainer from '@/components/public/PublicContainer.vue';
import PublicSection from '@/components/public/PublicSection.vue';
import SectionHeader from '@/components/public/SectionHeader.vue';
import ReviewForm from '@/components/reviews/ReviewForm.vue';
import ReviewList from '@/components/reviews/ReviewList.vue';
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/PublicLayout.vue';

interface Props {
    movie: Movie;
    reviews: {
        data: Review[];
        meta: PaginationMeta;
        links: { url: string | null; label: string; active: boolean }[];
    };
    queryParams?: {
        show_spoilers?: boolean;
        sort?: string;
    };
}

defineProps<Props>();

const showReviewForm = ref(false);
</script>

<template>
    <PublicLayout>
        <Head :title="`Reviews – ${movie.title}`" />

        <PublicSection spacing="md">
            <PublicContainer class="flex flex-col gap-6">
                <Link
                    :href="`/movies/${movie.slug}`"
                    class="inline-flex items-center gap-2 text-sm text-zinc-400 transition-colors hover:text-white"
                >
                    <ArrowLeft class="size-4" />
                    Back to {{ movie.title }}
                </Link>

                <!-- Movie header -->
                <div
                    class="flex flex-wrap items-start gap-6 rounded-lg border border-zinc-800 bg-zinc-900/50 p-6"
                >
                    <Poster
                        :src="movie.poster_url"
                        :alt="movie.title"
                        :poster-path="movie.poster_path"
                        context="detail"
                        class="shrink-0 rounded-lg"
                    />
                    <div class="min-w-0 flex-1">
                        <h1 class="text-2xl font-bold text-white">
                            {{ movie.title }}
                        </h1>
                        <p v-if="movie.release_year" class="mt-1 text-zinc-400">
                            {{ movie.release_year }}
                        </p>
                        <div class="mt-4 flex flex-wrap items-center gap-4">
                            <div
                                v-if="movie.tmdb_vote_average != null"
                                class="flex items-center gap-2 text-sm text-zinc-400"
                            >
                                <span
                                    >TMDB:
                                    {{
                                        movie.tmdb_vote_average.toFixed(1)
                                    }}/10</span
                                >
                                <span v-if="movie.tmdb_vote_count != null">
                                    ({{
                                        movie.tmdb_vote_count.toLocaleString()
                                    }}
                                    votes)
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Write review -->
                <div class="space-y-4">
                    <Button
                        v-if="!showReviewForm"
                        variant="outline"
                        class="border-zinc-700 text-zinc-300 hover:bg-zinc-800 hover:text-white"
                        @click="showReviewForm = true"
                    >
                        Write a review
                    </Button>
                    <div
                        v-else
                        class="rounded-lg border border-zinc-800 bg-zinc-900/50 p-6"
                    >
                        <h2 class="mb-4 text-lg font-semibold text-white">
                            Write a review
                        </h2>
                        <ReviewForm :movie="movie" :existing-review="null" />
                    </div>
                </div>

                <!-- Reviews list -->
                <div>
                    <SectionHeader
                        title="All reviews"
                        :level="2"
                        class="mb-4"
                    />
                    <ReviewList
                        :reviews="reviews"
                        :query-params="queryParams"
                        :movie-slug="movie.slug"
                    />
                </div>
            </PublicContainer>
        </PublicSection>
    </PublicLayout>
</template>
