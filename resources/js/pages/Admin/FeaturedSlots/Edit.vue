<script setup lang="ts">
import type { FeaturedSlot } from '@/types/models';

import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';

interface Props {
    slot: FeaturedSlot;
    movies: { id: number; title: string }[];
}

const props = defineProps<Props>();
usePage();

// Format datetime for datetime-local input
function formatDateTimeLocal(dateString: string | null): string {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toISOString().slice(0, 16);
}

const slot = computed(() => props.slot);
const form = useForm({
    movie_id: slot.value.movie_id,
    slot: slot.value.slot,
    starts_at: formatDateTimeLocal(slot.value.starts_at),
    ends_at: formatDateTimeLocal(slot.value.ends_at),
});

function submit() {
    form.put(`/featured-slots/${props.slot.id}`);
}
</script>

<template>
    <AppSidebarLayout>
        <Head title="Edit Featured Slot - Admin" />

        <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-white">
                    Edit Featured Slot
                </h1>
                <p class="mt-2 text-zinc-400">
                    Update featured slot configuration.
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Movie Selection -->
                <div>
                    <label
                        for="movie_id"
                        class="block text-sm font-medium text-zinc-300"
                    >
                        Movie <span class="text-red-500">*</span>
                    </label>
                    <select
                        id="movie_id"
                        v-model="form.movie_id"
                        required
                        class="mt-1 w-full rounded-lg border-zinc-700 bg-zinc-900 text-white focus:border-red-500 focus:ring-red-500"
                    >
                        <option :value="null" disabled>
                            Select a movie...
                        </option>
                        <option
                            v-for="movie in movies"
                            :key="movie.id"
                            :value="movie.id"
                        >
                            {{ movie.title }}
                        </option>
                    </select>
                    <div
                        v-if="form.errors.movie_id"
                        class="mt-1 text-sm text-red-500"
                    >
                        {{ form.errors.movie_id }}
                    </div>
                </div>

                <!-- Slot Type -->
                <div>
                    <label class="block text-sm font-medium text-zinc-300">
                        Slot Type <span class="text-red-500">*</span>
                    </label>
                    <div class="mt-2 grid gap-4 sm:grid-cols-2">
                        <label
                            class="flex cursor-pointer items-center gap-3 rounded-lg bg-zinc-900 p-4 hover:bg-zinc-800"
                            :class="{
                                'ring-2 ring-red-500': form.slot === 'hero',
                            }"
                        >
                            <input
                                v-model="form.slot"
                                type="radio"
                                value="hero"
                                class="shrink-0 text-red-600 focus:ring-red-500"
                            />
                            <div class="min-w-0 flex-1">
                                <div class="font-medium text-white">
                                    Hero Section
                                </div>
                                <div class="text-sm text-zinc-400">
                                    Large featured movie at top
                                </div>
                            </div>
                        </label>

                        <label
                            class="flex cursor-pointer items-center gap-3 rounded-lg bg-zinc-900 p-4 hover:bg-zinc-800"
                            :class="{
                                'ring-2 ring-red-500':
                                    form.slot === 'pick_of_week',
                            }"
                        >
                            <input
                                v-model="form.slot"
                                type="radio"
                                value="pick_of_week"
                                class="shrink-0 text-red-600 focus:ring-red-500"
                            />
                            <div class="min-w-0 flex-1">
                                <div class="font-medium text-white">
                                    Pick of the Week
                                </div>
                                <div class="text-sm text-zinc-400">
                                    Secondary featured movie
                                </div>
                            </div>
                        </label>
                    </div>
                    <div
                        v-if="form.errors.slot"
                        class="mt-1 text-sm text-red-500"
                    >
                        {{ form.errors.slot }}
                    </div>
                </div>

                <!-- Dates -->
                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label
                            for="starts_at"
                            class="block text-sm font-medium text-zinc-300"
                        >
                            Start Date <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="starts_at"
                            v-model="form.starts_at"
                            type="datetime-local"
                            required
                            class="mt-1 w-full rounded-lg border-zinc-700 bg-zinc-900 text-white focus:border-red-500 focus:ring-red-500"
                        />
                        <div
                            v-if="form.errors.starts_at"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ form.errors.starts_at }}
                        </div>
                    </div>

                    <div>
                        <label
                            for="ends_at"
                            class="block text-sm font-medium text-zinc-300"
                        >
                            End Date
                            <span class="text-zinc-500">(Optional)</span>
                        </label>
                        <input
                            id="ends_at"
                            v-model="form.ends_at"
                            type="datetime-local"
                            class="mt-1 w-full rounded-lg border-zinc-700 bg-zinc-900 text-white focus:border-red-500 focus:ring-red-500"
                        />
                        <p class="mt-1 text-xs text-zinc-500">
                            Leave empty for no end date
                        </p>
                        <div
                            v-if="form.errors.ends_at"
                            class="mt-1 text-sm text-red-500"
                        >
                            {{ form.errors.ends_at }}
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-4">
                    <Link
                        href="/dashboard/featured-slots"
                        class="rounded-lg bg-zinc-800 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50"
                    >
                        {{
                            form.processing
                                ? 'Updating...'
                                : 'Update Featured Slot'
                        }}
                    </button>
                </div>
            </form>
        </div>
    </AppSidebarLayout>
</template>
