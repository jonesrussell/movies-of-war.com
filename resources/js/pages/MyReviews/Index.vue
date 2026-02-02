<script setup lang="ts">
import type { PaginationMeta, Review } from '@/types/models';

import { Head, Link } from '@inertiajs/vue3';
import { Star } from 'lucide-vue-next';
import { computed } from 'vue';

import Pagination from '@/components/Pagination.vue';
import { StarRating } from '@/components/primitives';
import { Button } from '@/components/ui/button';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';

interface PaginatedReviews {
    data: Review[];
    meta: PaginationMeta;
}

interface Props {
    reviews: PaginatedReviews;
}

const props = defineProps<Props>();

const reviewsList = computed(() => props.reviews?.data ?? []);
</script>

<template>
    <AppSidebarLayout>
        <Head title="My Reviews - Movies of War" />

        <div class="w-full px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-8">
                <h1 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">
                    My Reviews
                </h1>
                <p class="mt-1 text-zinc-300">
                    {{ reviews?.meta?.total ?? 0 }}
                    {{ (reviews?.meta?.total ?? 0) === 1 ? 'review' : 'reviews' }}
                </p>
            </div>

            <div v-if="reviewsList.length > 0">
                <!-- Desktop: Table -->
                <div
                    class="hidden overflow-hidden rounded-xl bg-zinc-900 ring-1 ring-zinc-800/70 md:block"
                >
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-zinc-800">
                                <th
                                    class="px-5 py-4 text-left text-sm font-medium text-zinc-400"
                                >
                                    Movie
                                </th>
                                <th
                                    class="px-5 py-4 text-left text-sm font-medium text-zinc-400"
                                >
                                    Rating
                                </th>
                                <th
                                    class="px-5 py-4 text-left text-sm font-medium text-zinc-400"
                                >
                                    Date
                                </th>
                                <th
                                    class="px-5 py-4 text-right text-sm font-medium text-zinc-400"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="review in reviewsList"
                                :key="review.id"
                                class="border-b border-zinc-800/70 transition-colors last:border-0 hover:bg-zinc-800/30"
                            >
                                <td class="px-5 py-4">
                                    <Link
                                        :href="`/movies/${review.movie?.slug}`"
                                        class="font-medium text-white hover:text-red-400"
                                    >
                                        {{ review.movie?.title }}
                                    </Link>
                                </td>
                                <td class="px-5 py-4">
                                    <StarRating
                                        :rating="review.rating"
                                        :max-stars="4"
                                        size="sm"
                                    />
                                </td>
                                <td class="px-5 py-4 text-sm text-zinc-400">
                                    {{ review.formatted_date }}
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <Link
                                        :href="`/reviews/${review.id}`"
                                        class="text-sm text-red-500 hover:text-red-400"
                                    >
                                        View review
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile: Card grid -->
                <div class="flex flex-col gap-3 md:hidden">
                    <Link
                        v-for="review in reviewsList"
                        :key="review.id"
                        :href="`/reviews/${review.id}`"
                        class="flex items-center gap-4 rounded-xl bg-zinc-900 p-4 ring-1 ring-zinc-800/70 transition-colors hover:bg-zinc-800/70"
                    >
                        <div
                            v-if="review.movie?.poster_url"
                            class="size-12 shrink-0 overflow-hidden rounded-lg bg-zinc-800"
                        >
                            <img
                                :src="review.movie.poster_url"
                                :alt="review.movie?.title"
                                class="size-full object-cover"
                                loading="lazy"
                            />
                        </div>
                        <div
                            v-else
                            class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-zinc-800"
                        >
                            <Star class="size-6 text-zinc-500" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p
                                class="truncate font-medium text-white"
                            >
                                {{ review.movie?.title }}
                            </p>
                            <p class="text-sm text-zinc-400">
                                {{ review.formatted_date }}
                            </p>
                        </div>
                        <StarRating
                            :rating="review.rating"
                            :max-stars="4"
                            size="sm"
                        />
                    </Link>
                </div>

                <Pagination
                    v-if="reviews?.meta"
                    :meta="reviews.meta"
                />
            </div>

            <div
                v-else
                class="relative overflow-hidden rounded-2xl bg-zinc-950 p-10 text-center ring-1 ring-zinc-800/70"
            >
                <div
                    class="pointer-events-none absolute inset-0 [background-image:radial-gradient(circle_at_1px_1px,white_1px,transparent_0)] [background-size:22px_22px] opacity-[0.10]"
                />
                <div class="relative">
                    <div
                        class="mx-auto mb-6 flex size-16 items-center justify-center rounded-full bg-amber-500/10"
                    >
                        <Star class="size-8 text-amber-400" />
                    </div>
                    <h2 class="text-2xl font-semibold tracking-tight text-balance text-white">
                        No reviews yet
                    </h2>
                    <p class="mx-auto mt-3 max-w-lg text-zinc-400">
                        Share your thoughts on war films. Browse the collection
                        and write your first review.
                    </p>
                    <div class="mt-7 flex justify-center">
                        <Button as-child>
                            <Link href="/movies">Browse movies</Link>
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppSidebarLayout>
</template>
