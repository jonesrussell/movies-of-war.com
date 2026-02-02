<script setup lang="ts">
import type { PaginationMeta, Review } from '@/types/models';

import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { computed, ref, watch } from 'vue';

import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';

interface PaginatedReviews {
    data: Review[];
    links: { url: string | null; label: string; active: boolean }[];
    meta?: PaginationMeta;
    last_page?: number;
    total?: number;
}

interface Props {
    reviews: PaginatedReviews;
    queryParams: {
        search?: string;
        published?: string;
    };
}

const props = defineProps<Props>();

const queryParams = computed(() => props.queryParams);
const search = ref(queryParams.value?.search ?? '');
const publishedFilter = ref(queryParams.value?.published ?? '');

const debouncedSearch = useDebounceFn((searchValue: string) => {
    router.get(
        '/dashboard/reviews',
        {
            search: searchValue || undefined,
            published: publishedFilter.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
}, 300);

watch(search, (value) => {
    void debouncedSearch(value);
});

function filterByPublished(value: string) {
    publishedFilter.value = value;
    router.get(
        '/dashboard/reviews',
        {
            search: search.value || undefined,
            published: value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
}

function formatDate(dateString: string | null): string {
    if (!dateString) {
        return '-';
    }
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function togglePublished(review: Review) {
    router.post(
        `/dashboard/reviews/${review.id}/toggle-published`,
        {},
        { preserveScroll: true },
    );
}

function deleteReview(review: Review) {
    if (
        confirm(
            'Are you sure you want to delete this review? This cannot be undone.',
        )
    ) {
        router.delete(`/dashboard/reviews/${review.id}`, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <AppSidebarLayout>
        <Head title="Manage Reviews - Admin" />

        <div class="w-full px-4 py-12 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-white">Manage Reviews</h1>
                <p class="mt-2 text-zinc-300">
                    {{ reviews?.meta?.total ?? reviews?.total ?? 0 }} total
                    reviews
                </p>
            </div>

            <!-- Filters -->
            <div class="mb-6 flex flex-col gap-4 sm:flex-row">
                <div class="flex-1">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search by content, title, author, or movie..."
                        class="w-full rounded-lg border-zinc-700 bg-zinc-900 px-4 py-2 text-white placeholder-zinc-500 focus:border-red-500 focus:ring-red-500"
                    />
                </div>
                <div class="flex gap-2">
                    <button
                        @click="filterByPublished('')"
                        :class="[
                            'rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                            !publishedFilter
                                ? 'bg-red-600 text-white'
                                : 'bg-zinc-800 text-zinc-300 hover:bg-zinc-700',
                        ]"
                    >
                        All
                    </button>
                    <button
                        @click="filterByPublished('1')"
                        :class="[
                            'rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                            publishedFilter === '1'
                                ? 'bg-red-600 text-white'
                                : 'bg-zinc-800 text-zinc-300 hover:bg-zinc-700',
                        ]"
                    >
                        Published
                    </button>
                    <button
                        @click="filterByPublished('0')"
                        :class="[
                            'rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                            publishedFilter === '0'
                                ? 'bg-red-600 text-white'
                                : 'bg-zinc-800 text-zinc-300 hover:bg-zinc-700',
                        ]"
                    >
                        Unpublished
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-lg bg-zinc-900">
                <table class="min-w-full divide-y divide-zinc-800">
                    <thead class="bg-zinc-950">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-zinc-300 uppercase"
                            >
                                Review
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-zinc-300 uppercase"
                            >
                                Movie
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-zinc-300 uppercase"
                            >
                                Author
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-zinc-300 uppercase"
                            >
                                Rating
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-zinc-300 uppercase"
                            >
                                Status
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-zinc-300 uppercase"
                            >
                                Date
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium tracking-wider text-zinc-300 uppercase"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-800">
                        <tr
                            v-if="!reviews?.data?.length"
                            class="hover:bg-zinc-800/50"
                        >
                            <td
                                colspan="7"
                                class="px-6 py-12 text-center text-zinc-300"
                            >
                                No reviews found.
                            </td>
                        </tr>
                        <tr
                            v-for="review in reviews?.data"
                            :key="review.id"
                            class="hover:bg-zinc-800/50"
                        >
                            <td class="px-6 py-4">
                                <div class="max-w-xs">
                                    <div
                                        v-if="review.title"
                                        class="font-medium text-white"
                                    >
                                        {{ review.title }}
                                    </div>
                                    <div
                                        class="text-sm text-zinc-300"
                                        :class="{ 'mt-1': review.title }"
                                    >
                                        {{
                                            review.content_excerpt ||
                                            '(No content)'
                                        }}
                                    </div>
                                    <div
                                        v-if="review.has_spoilers"
                                        class="mt-1 text-xs text-yellow-500"
                                    >
                                        Contains spoilers
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <Link
                                    v-if="review.movie"
                                    :href="`/movies/${review.movie.slug}`"
                                    class="text-red-500 hover:text-red-400"
                                >
                                    {{ review.movie.title }}
                                </Link>
                                <span v-else class="text-zinc-500">—</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-zinc-300">
                                {{ review.user?.name ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-zinc-300">
                                {{ review.stars }} ({{ review.rating }}/4)
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    :class="[
                                        'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                        review.is_published
                                            ? 'bg-green-900/50 text-green-300'
                                            : 'bg-yellow-900/50 text-yellow-300',
                                    ]"
                                >
                                    {{
                                        review.is_published
                                            ? 'Published'
                                            : 'Unpublished'
                                    }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-zinc-300">
                                {{ formatDate(review.created_at) }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Link
                                        :href="`/reviews/${review.id}`"
                                        class="text-red-500 hover:text-red-400"
                                    >
                                        View
                                    </Link>
                                    <button
                                        @click="togglePublished(review)"
                                        type="button"
                                        class="text-blue-500 hover:text-blue-400"
                                    >
                                        {{
                                            review.is_published
                                                ? 'Unpublish'
                                                : 'Publish'
                                        }}
                                    </button>
                                    <button
                                        @click="deleteReview(review)"
                                        type="button"
                                        class="text-zinc-300 hover:text-white"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="reviews?.meta?.last_page && reviews.meta.last_page > 1"
                class="mt-6 flex justify-center gap-2"
            >
                <Link
                    v-for="link in reviews.meta.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    :class="[
                        'rounded px-3 py-2 text-sm',
                        link.active
                            ? 'bg-red-600 text-white'
                            : link.url
                              ? 'bg-zinc-800 text-zinc-300 hover:bg-zinc-700'
                              : 'cursor-not-allowed bg-zinc-900 text-zinc-600',
                    ]"
                >
                    <!-- eslint-disable-next-line vue/no-v-html -->
                    <span v-html="link.label" />
                </Link>
            </div>
            <div
                v-else-if="
                    reviews?.links?.length && (reviews.last_page ?? 0) > 1
                "
                class="mt-6 flex justify-center gap-2"
            >
                <Link
                    v-for="link in reviews.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    :class="[
                        'rounded px-3 py-2 text-sm',
                        link.active
                            ? 'bg-red-600 text-white'
                            : link.url
                              ? 'bg-zinc-800 text-zinc-300 hover:bg-zinc-700'
                              : 'cursor-not-allowed bg-zinc-900 text-zinc-600',
                    ]"
                >
                    <!-- eslint-disable-next-line vue/no-v-html -->
                    <span v-html="link.label" />
                </Link>
            </div>
        </div>
    </AppSidebarLayout>
</template>
