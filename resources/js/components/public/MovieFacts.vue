<script setup lang="ts">
import type { Movie } from '@/types';

import { Calendar, Clock, Film, MapPin } from 'lucide-vue-next';

defineProps<{ movie: Movie }>();
</script>

<template>
    <dl
        class="grid gap-3 rounded-2xl bg-zinc-950/60 p-5 ring-1 ring-zinc-800/70 sm:grid-cols-2"
    >
        <div class="flex items-center gap-2 text-sm text-zinc-200">
            <dt class="sr-only">Release year</dt>
            <dd class="flex items-center gap-2 font-semibold">
                <Calendar class="size-4 shrink-0 text-zinc-300" aria-hidden="true" />
                {{ movie.release_year }}
            </dd>
        </div>

        <div
            v-if="movie.runtime"
            class="flex items-center gap-2 text-sm text-zinc-200"
        >
            <dt class="sr-only">Runtime</dt>
            <dd class="flex items-center gap-2">
                <Clock class="size-4 shrink-0 text-zinc-300" aria-hidden="true" />
                {{ movie.runtime }} min
            </dd>
        </div>

        <div
            v-if="movie.country"
            class="flex items-center gap-2 text-sm text-zinc-200"
        >
            <dt class="sr-only">Country</dt>
            <dd class="flex items-center gap-2">
                <MapPin class="size-4 shrink-0 text-zinc-300" aria-hidden="true" />
                {{ movie.country }}
            </dd>
        </div>

        <div
            v-if="movie.conflict"
            class="flex items-center gap-2 text-sm text-zinc-200"
        >
            <dt class="sr-only">Conflict</dt>
            <dd class="flex items-center gap-2">
                <Film class="size-4 shrink-0 text-zinc-300" aria-hidden="true" />
                {{ movie.conflict }}
            </dd>
        </div>
    </dl>
</template>
