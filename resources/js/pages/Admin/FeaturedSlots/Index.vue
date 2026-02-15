<script setup lang="ts">
import type { FeaturedSlot } from '@/types/models';
import type { PaginationMeta } from '@/types/models';

import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ChevronDown, ChevronUp } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import Pagination from '@/components/Pagination.vue';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';

const PER_PAGE_OPTIONS = [10, 20, 50, 100] as const;

interface SlotsPayload {
    data: FeaturedSlot[];
    meta: PaginationMeta;
}

interface Props {
    slots: SlotsPayload;
    queryParams: {
        sort?: string;
        per_page?: number;
    };
}

const props = defineProps<Props>();
usePage();

const queryParams = computed(() => props.queryParams ?? {});
const perPage = ref(queryParams.value?.per_page ?? 20);
const currentSort = computed(
    () => queryParams.value?.sort ?? 'created_at_desc',
);

function applyFilters(
    updates: Record<string, string | number | undefined> = {},
) {
    const resetPage = ['sort', 'per_page'].some(
        (k) => updates[k] !== undefined,
    );
    router.get(
        '/dashboard/featured-slots',
        {
            sort: currentSort.value || undefined,
            per_page: perPage.value,
            ...updates,
            ...(resetPage ? { page: 1 } : {}),
        } as Record<string, string | number | undefined>,
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
}

function applySort(column: string) {
    const dir = currentSort.value === `${column}_asc` ? 'desc' : 'asc';
    applyFilters({ sort: `${column}_${dir}` });
}

function sortDirection(key: string): 'asc' | 'desc' | null {
    if (currentSort.value === `${key}_asc`) return 'asc';
    if (currentSort.value === `${key}_desc`) return 'desc';
    return null;
}

function setPerPage(n: number) {
    perPage.value = n;
    applyFilters({ per_page: n });
}

const paginationSummary = computed(() => {
    const m = props.slots?.meta;
    if (!m || m.total === 0) return null;
    const from = m.from ?? 0;
    const to = m.to ?? 0;
    const total = m.total;
    return `Showing ${from}–${to} of ${total}`;
});

function deleteSlot(slot: FeaturedSlot) {
    if (confirm(`Are you sure you want to delete this featured slot?`)) {
        router.delete(`/featured-slots/${slot.id}`);
    }
}

function getSlotBadge(slot: string): string {
    return slot === 'hero' ? 'Hero' : 'Pick of Week';
}
</script>

<template>
    <AppSidebarLayout>
        <Head title="Manage Featured Slots - Admin" />

        <div class="w-full px-4 py-12 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white">
                        Featured Slots
                    </h1>
                    <p class="mt-2 text-zinc-400">
                        Manage homepage hero and pick of the week
                    </p>
                </div>
                <Link
                    :href="'/featured-slots/create'"
                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                >
                    Add Featured Slot
                </Link>
            </div>

            <!-- Filters -->
            <div class="mb-6 flex flex-wrap items-center gap-4">
                <div class="flex items-center gap-2">
                    <label for="slots-per-page" class="text-sm text-zinc-400"
                        >Per page</label
                    >
                    <select
                        id="slots-per-page"
                        :value="perPage"
                        class="rounded-lg border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-white focus:border-red-500 focus:ring-red-500"
                        @change="
                            setPerPage(
                                Number(
                                    ($event.target as HTMLSelectElement).value,
                                ),
                            )
                        "
                    >
                        <option
                            v-for="n in PER_PAGE_OPTIONS"
                            :key="n"
                            :value="n"
                        >
                            {{ n }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Pagination summary -->
            <div v-if="paginationSummary" class="mb-2 text-sm text-zinc-400">
                {{ paginationSummary }}
            </div>

            <!-- Slots Table -->
            <div class="overflow-hidden rounded-lg bg-zinc-900">
                <table class="min-w-full divide-y divide-zinc-800">
                    <thead class="bg-zinc-950">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-zinc-400 uppercase"
                            >
                                <button
                                    type="button"
                                    class="flex items-center gap-1 hover:text-white"
                                    @click="applySort('movie_title')"
                                >
                                    Movie
                                    <ChevronUp
                                        v-if="
                                            sortDirection('movie_title') ===
                                            'asc'
                                        "
                                        class="size-4"
                                    />
                                    <ChevronDown
                                        v-else-if="
                                            sortDirection('movie_title') ===
                                            'desc'
                                        "
                                        class="size-4"
                                    />
                                </button>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-zinc-400 uppercase"
                            >
                                <button
                                    type="button"
                                    class="flex items-center gap-1 hover:text-white"
                                    @click="applySort('slot')"
                                >
                                    Slot Type
                                    <ChevronUp
                                        v-if="sortDirection('slot') === 'asc'"
                                        class="size-4"
                                    />
                                    <ChevronDown
                                        v-else-if="
                                            sortDirection('slot') === 'desc'
                                        "
                                        class="size-4"
                                    />
                                </button>
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium tracking-wider text-zinc-400 uppercase"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-800">
                        <tr
                            v-for="slot in slots.data"
                            :key="slot.id"
                            class="hover:bg-zinc-800/50"
                        >
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <img
                                        v-if="slot.movie?.poster_url"
                                        :src="slot.movie.poster_url"
                                        :alt="slot.movie.title"
                                        class="h-12 w-8 rounded object-cover"
                                    />
                                    <div
                                        class="h-12 w-8 rounded bg-zinc-800"
                                        v-else
                                    />
                                    <div class="font-medium text-white">
                                        {{ slot.movie?.title }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    :class="[
                                        'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                        slot.slot === 'hero'
                                            ? 'bg-red-900/50 text-red-300'
                                            : 'bg-blue-900/50 text-blue-300',
                                    ]"
                                >
                                    {{ getSlotBadge(slot.slot) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <Link
                                    :href="`/featured-slots/${slot.id}/edit`"
                                    class="mr-4 text-red-500 hover:text-red-400"
                                >
                                    Edit
                                </Link>
                                <button
                                    @click="deleteSlot(slot)"
                                    class="text-zinc-400 hover:text-white"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <Pagination v-if="slots?.meta" :meta="slots.meta" />
        </div>
    </AppSidebarLayout>
</template>
