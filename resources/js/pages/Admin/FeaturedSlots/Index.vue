<script setup lang="ts">
import type { FeaturedSlot, PaginatedFeaturedSlots } from '@/types/models';

import { Head, Link, router, usePage } from '@inertiajs/vue3';

import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';

interface Props {
    slots: PaginatedFeaturedSlots;
}

defineProps<Props>();
usePage();

function deleteSlot(slot: FeaturedSlot) {
    if (confirm(`Are you sure you want to delete this featured slot?`)) {
        router.delete(`/featured-slots/${slot.id}`);
    }
}

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function getSlotBadge(slot: string): string {
    return slot === 'hero' ? 'Hero' : 'Pick of Week';
}

function isActive(slot: FeaturedSlot): boolean {
    const now = new Date();
    const start = new Date(slot.starts_at);
    const end = slot.ends_at ? new Date(slot.ends_at) : null;

    return start <= now && (!end || end >= now);
}
</script>

<template>
    <AppSidebarLayout>
        <Head title="Manage Featured Slots - Admin" />

        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
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

            <!-- Slots Table -->
            <div class="overflow-hidden rounded-lg bg-zinc-900">
                <table class="min-w-full divide-y divide-zinc-800">
                    <thead class="bg-zinc-950">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-zinc-400 uppercase"
                            >
                                Movie
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-zinc-400 uppercase"
                            >
                                Slot Type
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-zinc-400 uppercase"
                            >
                                Starts
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-zinc-400 uppercase"
                            >
                                Ends
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-zinc-400 uppercase"
                            >
                                Status
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
                            <td class="px-6 py-4 text-sm text-zinc-300">
                                {{ formatDate(slot.starts_at) }}
                            </td>
                            <td class="px-6 py-4 text-sm text-zinc-300">
                                {{
                                    slot.ends_at
                                        ? formatDate(slot.ends_at)
                                        : 'No end date'
                                }}
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    :class="[
                                        'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                        isActive(slot)
                                            ? 'bg-green-900/50 text-green-300'
                                            : 'bg-zinc-800 text-zinc-400',
                                    ]"
                                >
                                    {{ isActive(slot) ? 'Active' : 'Inactive' }}
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
            <div
                v-if="slots.last_page > 1"
                class="mt-6 flex justify-center gap-2"
            >
                <Link
                    v-for="page in slots.links"
                    :key="page.label"
                    :href="page.url || '#'"
                    :class="[
                        'rounded px-3 py-2 text-sm',
                        page.active
                            ? 'bg-red-600 text-white'
                            : page.url
                              ? 'bg-zinc-800 text-zinc-300 hover:bg-zinc-700'
                              : 'cursor-not-allowed bg-zinc-900 text-zinc-600',
                    ]"
                >
                    <!-- eslint-disable-next-line vue/no-v-html -->
                    <span v-html="page.label" />
                </Link>
            </div>
        </div>
    </AppSidebarLayout>
</template>
