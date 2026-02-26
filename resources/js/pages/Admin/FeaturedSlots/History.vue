<script setup lang="ts">
import type { FeaturedSlotHistory, PaginationMeta } from '@/types/models';

import { Head, Link, router } from '@inertiajs/vue3';

import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';

interface Props {
    history: {
        data: FeaturedSlotHistory[];
        meta: PaginationMeta;
    };
    queryParams: {
        sort?: string;
        per_page?: number;
        slot?: string;
        method?: string;
    };
}

const props = defineProps<Props>();

function applyFilter(key: string, value: string | null) {
    const params: Record<string, string | number | null | undefined> = {
        ...props.queryParams,
        [key]: value,
    };
    if (!value) {
        delete params[key];
    }
    router.get('/dashboard/featured-history', params as Record<string, string>, {
        preserveState: true,
    });
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '\u2014';
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function slotLabel(slot: string): string {
    return slot === 'hero' ? 'Hero' : 'Pick of the Week';
}
</script>

<template>
    <AppSidebarLayout>
        <Head title="Featured Slot History - Admin" />

        <div class="w-full px-4 py-12 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white">
                        Featured Slot History
                    </h1>
                    <p class="mt-2 text-zinc-400">
                        Complete log of all featured slot rotations
                    </p>
                </div>
                <Link
                    href="/dashboard/featured-queue"
                    class="rounded-lg border border-zinc-700 px-4 py-2 text-sm font-medium text-zinc-300 hover:bg-zinc-800"
                >
                    View Queue
                </Link>
            </div>

            <!-- Filters -->
            <div class="mb-6 flex flex-wrap gap-3">
                <select
                    :value="queryParams.slot ?? ''"
                    class="rounded-lg border-zinc-700 bg-zinc-900 text-sm text-white"
                    @change="
                        applyFilter(
                            'slot',
                            ($event.target as HTMLSelectElement).value ||
                                null,
                        )
                    "
                >
                    <option value="">All Slots</option>
                    <option value="hero">Hero</option>
                    <option value="pick_of_week">
                        Pick of the Week
                    </option>
                </select>
                <select
                    :value="queryParams.method ?? ''"
                    class="rounded-lg border-zinc-700 bg-zinc-900 text-sm text-white"
                    @change="
                        applyFilter(
                            'method',
                            ($event.target as HTMLSelectElement).value ||
                                null,
                        )
                    "
                >
                    <option value="">All Methods</option>
                    <option value="auto">Auto</option>
                    <option value="manual">Manual</option>
                </select>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-lg border border-zinc-800">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-zinc-800 bg-zinc-900">
                        <tr>
                            <th
                                class="px-4 py-3 font-medium text-zinc-400"
                            >
                                Movie
                            </th>
                            <th
                                class="px-4 py-3 font-medium text-zinc-400"
                            >
                                Slot
                            </th>
                            <th
                                class="px-4 py-3 font-medium text-zinc-400"
                            >
                                Method
                            </th>
                            <th
                                class="px-4 py-3 font-medium text-zinc-400"
                            >
                                Started
                            </th>
                            <th
                                class="px-4 py-3 font-medium text-zinc-400"
                            >
                                Ended
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-800">
                        <tr
                            v-for="entry in history.data"
                            :key="entry.id"
                            class="bg-zinc-950"
                        >
                            <td class="px-4 py-3 text-white">
                                {{
                                    entry.movie?.title ?? 'Deleted Movie'
                                }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="
                                        entry.slot === 'hero'
                                            ? 'bg-red-500/10 text-red-400'
                                            : 'bg-blue-500/10 text-blue-400'
                                    "
                                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                                >
                                    {{ slotLabel(entry.slot) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="
                                        entry.selection_method ===
                                        'manual'
                                            ? 'text-amber-400'
                                            : 'text-emerald-400'
                                    "
                                    class="text-xs font-medium"
                                >
                                    {{ entry.selection_method }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-zinc-400">
                                {{ formatDate(entry.started_at) }}
                            </td>
                            <td class="px-4 py-3 text-zinc-400">
                                <template v-if="entry.ended_at">
                                    {{ formatDate(entry.ended_at) }}
                                </template>
                                <span
                                    v-else
                                    class="text-emerald-400"
                                >
                                    Current
                                </span>
                            </td>
                        </tr>
                        <tr v-if="history.data.length === 0">
                            <td
                                colspan="5"
                                class="px-4 py-8 text-center text-zinc-500"
                            >
                                No history yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="history.meta.last_page > 1"
                class="mt-6 flex justify-center gap-1"
            >
                <template
                    v-for="link in history.meta.links"
                    :key="link.label"
                >
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded px-3 py-1 text-sm"
                        :class="
                            link.active
                                ? 'bg-red-600 text-white'
                                : 'text-zinc-400 hover:bg-zinc-800'
                        "
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="px-3 py-1 text-sm text-zinc-600"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </AppSidebarLayout>
</template>
