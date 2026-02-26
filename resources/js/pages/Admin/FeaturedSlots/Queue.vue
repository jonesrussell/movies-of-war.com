<script setup lang="ts">
import type { FeaturedSlotQueue, Movie } from '@/types/models';

import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';

interface Props {
    heroQueue: FeaturedSlotQueue[];
    pickOfWeekQueue: FeaturedSlotQueue[];
    movies: Pick<Movie, 'id' | 'title' | 'release_year'>[];
}

const props = defineProps<Props>();

const showAddForm = ref(false);
const addSlot = ref<'hero' | 'pick_of_week'>('hero');
const addMovieId = ref<number | null>(null);
const addPosition = ref(1);

function submitAdd() {
    if (!addMovieId.value) return;
    router.post('/dashboard/featured-queue', {
        movie_id: addMovieId.value,
        slot: addSlot.value,
        position: addPosition.value,
    }, {
        onSuccess: () => {
            showAddForm.value = false;
            addMovieId.value = null;
            addPosition.value = 1;
        },
    });
}

function removeEntry(entry: FeaturedSlotQueue) {
    if (!confirm(`Remove "${entry.movie?.title}" from the queue?`)) return;
    router.delete(`/dashboard/featured-queue/${entry.id}`);
}

function refillQueue() {
    router.post('/dashboard/featured-queue/refill');
}

function slotLabel(slot: string): string {
    return slot === 'hero' ? 'Hero' : 'Pick of the Week';
}

const queues = {
    hero: () => props.heroQueue,
    pick_of_week: () => props.pickOfWeekQueue,
};
</script>

<template>
    <AppSidebarLayout>
        <Head title="Featured Slot Queue - Admin" />

        <div class="w-full px-4 py-12 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white">
                        Featured Slot Queue
                    </h1>
                    <p class="mt-2 text-zinc-400">
                        Upcoming featured slot rotations — next change
                        every Sunday 6 AM UTC
                    </p>
                </div>
                <div class="flex gap-3">
                    <Link
                        href="/dashboard/featured-slots"
                        class="rounded-lg border border-zinc-700 px-4 py-2 text-sm font-medium text-zinc-300 hover:bg-zinc-800"
                    >
                        Now Showing
                    </Link>
                    <button
                        class="rounded-lg border border-zinc-700 px-4 py-2 text-sm font-medium text-zinc-300 hover:bg-zinc-800"
                        @click="refillQueue"
                    >
                        Refill Queue
                    </button>
                    <button
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                        @click="showAddForm = !showAddForm"
                    >
                        Add Override
                    </button>
                </div>
            </div>

            <!-- Add Override Form -->
            <div
                v-if="showAddForm"
                class="mb-8 rounded-lg border border-zinc-700 bg-zinc-900 p-6"
            >
                <h2 class="mb-4 text-lg font-semibold text-white">
                    Add Manual Override
                </h2>
                <form
                    class="flex flex-wrap items-end gap-4"
                    @submit.prevent="submitAdd"
                >
                    <div>
                        <label
                            for="queue-movie"
                            class="block text-sm font-medium text-zinc-300"
                        >
                            Movie
                        </label>
                        <select
                            id="queue-movie"
                            v-model="addMovieId"
                            class="mt-1 rounded-lg border-zinc-700 bg-zinc-800 text-white"
                        >
                            <option :value="null" disabled>
                                Select movie...
                            </option>
                            <option
                                v-for="movie in movies"
                                :key="movie.id"
                                :value="movie.id"
                            >
                                {{ movie.title }} ({{ movie.release_year }})
                            </option>
                        </select>
                    </div>
                    <div>
                        <label
                            for="queue-slot"
                            class="block text-sm font-medium text-zinc-300"
                        >
                            Slot
                        </label>
                        <select
                            id="queue-slot"
                            v-model="addSlot"
                            class="mt-1 rounded-lg border-zinc-700 bg-zinc-800 text-white"
                        >
                            <option value="hero">Hero</option>
                            <option value="pick_of_week">
                                Pick of the Week
                            </option>
                        </select>
                    </div>
                    <div>
                        <label
                            for="queue-position"
                            class="block text-sm font-medium text-zinc-300"
                        >
                            Position
                        </label>
                        <input
                            id="queue-position"
                            v-model.number="addPosition"
                            type="number"
                            min="1"
                            class="mt-1 w-20 rounded-lg border-zinc-700 bg-zinc-800 text-white"
                        />
                    </div>
                    <button
                        type="submit"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                    >
                        Insert
                    </button>
                    <button
                        type="button"
                        class="rounded-lg border border-zinc-700 px-4 py-2 text-sm font-medium text-zinc-300 hover:bg-zinc-800"
                        @click="showAddForm = false"
                    >
                        Cancel
                    </button>
                </form>
            </div>

            <!-- Queue Columns -->
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                <div
                    v-for="(getQueue, slotType) in queues"
                    :key="slotType"
                >
                    <h2 class="mb-4 text-xl font-semibold text-white">
                        {{ slotLabel(slotType) }}
                    </h2>
                    <div
                        v-if="getQueue().length === 0"
                        class="rounded-lg border border-zinc-800 bg-zinc-900 p-6 text-center text-zinc-500"
                    >
                        Queue empty — click "Refill Queue" to
                        auto-populate.
                    </div>
                    <div v-else class="space-y-3">
                        <div
                            v-for="entry in getQueue()"
                            :key="entry.id"
                            class="flex items-center gap-4 rounded-lg border border-zinc-800 bg-zinc-900 p-4"
                        >
                            <span
                                class="flex size-8 shrink-0 items-center justify-center rounded-full bg-zinc-800 text-sm font-bold text-white"
                            >
                                {{ entry.position }}
                            </span>
                            <img
                                v-if="entry.movie?.poster_url"
                                :src="entry.movie.poster_url"
                                :alt="entry.movie.title"
                                class="h-12 w-8 shrink-0 rounded object-cover"
                            />
                            <div
                                v-else
                                class="h-12 w-8 shrink-0 rounded bg-zinc-800"
                            />
                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate font-medium text-white"
                                >
                                    {{
                                        entry.movie?.title ?? 'Unknown'
                                    }}
                                </p>
                                <p class="text-sm text-zinc-500">
                                    {{ entry.scheduled_for }}
                                </p>
                            </div>
                            <span
                                :class="
                                    entry.selection_method === 'manual'
                                        ? 'bg-amber-500/10 text-amber-400'
                                        : 'bg-emerald-500/10 text-emerald-400'
                                "
                                class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium"
                            >
                                {{ entry.selection_method }}
                            </span>
                            <button
                                class="shrink-0 text-zinc-500 hover:text-red-400"
                                title="Remove from queue"
                                @click="removeEntry(entry)"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="size-5"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Link to History -->
            <div class="mt-12 text-center">
                <Link
                    href="/dashboard/featured-history"
                    class="text-sm text-zinc-400 hover:text-white"
                >
                    View rotation history &rarr;
                </Link>
            </div>
        </div>
    </AppSidebarLayout>
</template>
