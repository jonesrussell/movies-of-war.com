<script setup lang="ts">
import type { Movie } from '@/types';

import {
    Calendar,
    Clapperboard,
    Clock,
    DollarSign,
    Globe,
    MapPin,
    PenLine,
    Star,
    Swords,
    User,
} from 'lucide-vue-next';

import {
    formatCurrency,
    formatReleaseDate,
    getLanguageName,
} from '@/utils/format';

defineProps<{ movie: Movie }>();
</script>

<template>
    <dl
        class="grid gap-0 rounded-xl border border-[--intel-border] bg-[--intel-bg-surface] sm:grid-cols-2"
    >
        <div
            class="flex items-center gap-3 border-b border-[--intel-border] px-5 py-3"
        >
            <dt
                class="flex shrink-0 items-center gap-2 text-xs font-semibold tracking-wide text-[--intel-text-muted] uppercase"
            >
                <Calendar
                    class="size-4 text-[--intel-text-muted]"
                    aria-hidden="true"
                />
                Release
            </dt>
            <dd
                class="font-[family-name:var(--font-mono-display)] text-sm text-[--intel-text-primary]"
            >
                {{ formatReleaseDate(movie.release_date, movie.release_year) }}
            </dd>
        </div>

        <div
            v-if="movie.tmdb_vote_average"
            class="flex items-center gap-3 border-b border-[--intel-border] px-5 py-3"
        >
            <dt
                class="flex shrink-0 items-center gap-2 text-xs font-semibold tracking-wide text-[--intel-text-muted] uppercase"
            >
                <Star
                    class="size-4 text-[--intel-text-muted]"
                    aria-hidden="true"
                />
                Rating
            </dt>
            <dd
                class="font-[family-name:var(--font-mono-display)] text-sm text-[--intel-text-primary]"
            >
                {{ movie.tmdb_vote_average }}/10 (TMDB)
            </dd>
        </div>

        <div
            v-if="movie.runtime"
            class="flex items-center gap-3 border-b border-[--intel-border] px-5 py-3"
        >
            <dt
                class="flex shrink-0 items-center gap-2 text-xs font-semibold tracking-wide text-[--intel-text-muted] uppercase"
            >
                <Clock
                    class="size-4 text-[--intel-text-muted]"
                    aria-hidden="true"
                />
                Runtime
            </dt>
            <dd
                class="font-[family-name:var(--font-mono-display)] text-sm text-[--intel-text-primary]"
            >
                {{ movie.runtime }} min
            </dd>
        </div>

        <div
            v-if="movie.country"
            class="flex items-center gap-3 border-b border-[--intel-border] px-5 py-3"
        >
            <dt
                class="flex shrink-0 items-center gap-2 text-xs font-semibold tracking-wide text-[--intel-text-muted] uppercase"
            >
                <MapPin
                    class="size-4 text-[--intel-text-muted]"
                    aria-hidden="true"
                />
                Country
            </dt>
            <dd
                class="font-[family-name:var(--font-mono-display)] text-sm text-[--intel-text-primary]"
            >
                {{ movie.country }}
            </dd>
        </div>

        <div
            v-if="movie.production_status"
            class="flex items-center gap-3 border-b border-[--intel-border] px-5 py-3"
        >
            <dt
                class="flex shrink-0 items-center gap-2 text-xs font-semibold tracking-wide text-[--intel-text-muted] uppercase"
            >
                <Clapperboard
                    class="size-4 text-[--intel-text-muted]"
                    aria-hidden="true"
                />
                Status
            </dt>
            <dd
                class="font-[family-name:var(--font-mono-display)] text-sm text-[--intel-text-primary]"
            >
                {{ movie.production_status }}
            </dd>
        </div>

        <div
            v-if="movie.original_language"
            class="flex items-center gap-3 border-b border-[--intel-border] px-5 py-3"
        >
            <dt
                class="flex shrink-0 items-center gap-2 text-xs font-semibold tracking-wide text-[--intel-text-muted] uppercase"
            >
                <Globe
                    class="size-4 text-[--intel-text-muted]"
                    aria-hidden="true"
                />
                Language
            </dt>
            <dd
                class="font-[family-name:var(--font-mono-display)] text-sm text-[--intel-text-primary]"
            >
                {{ getLanguageName(movie.original_language) }}
            </dd>
        </div>

        <div
            v-if="movie.budget"
            class="flex items-center gap-3 border-b border-[--intel-border] px-5 py-3"
        >
            <dt
                class="flex shrink-0 items-center gap-2 text-xs font-semibold tracking-wide text-[--intel-text-muted] uppercase"
            >
                <DollarSign
                    class="size-4 text-[--intel-text-muted]"
                    aria-hidden="true"
                />
                Budget
            </dt>
            <dd
                class="font-[family-name:var(--font-mono-display)] text-sm text-[--intel-text-primary]"
            >
                {{ formatCurrency(movie.budget) }}
            </dd>
        </div>

        <div
            v-if="movie.revenue"
            class="flex items-center gap-3 border-b border-[--intel-border] px-5 py-3"
        >
            <dt
                class="flex shrink-0 items-center gap-2 text-xs font-semibold tracking-wide text-[--intel-text-muted] uppercase"
            >
                <DollarSign
                    class="size-4 text-[--intel-text-muted]"
                    aria-hidden="true"
                />
                Revenue
            </dt>
            <dd
                class="font-[family-name:var(--font-mono-display)] text-sm text-[--intel-text-primary]"
            >
                {{ formatCurrency(movie.revenue) }}
            </dd>
        </div>

        <div
            v-if="movie.conflict"
            class="flex items-center gap-3 border-b border-[--intel-border] px-5 py-3"
        >
            <dt
                class="flex shrink-0 items-center gap-2 text-xs font-semibold tracking-wide text-[--intel-text-muted] uppercase"
            >
                <Swords
                    class="size-4 text-[--intel-text-muted]"
                    aria-hidden="true"
                />
                Conflict
            </dt>
            <dd
                class="font-[family-name:var(--font-mono-display)] text-sm text-[--intel-text-primary]"
            >
                {{ movie.conflict }}
            </dd>
        </div>

        <div
            v-if="movie.director"
            class="flex items-center gap-3 border-b border-[--intel-border] px-5 py-3"
        >
            <dt
                class="flex shrink-0 items-center gap-2 text-xs font-semibold tracking-wide text-[--intel-text-muted] uppercase"
            >
                <User
                    class="size-4 text-[--intel-text-muted]"
                    aria-hidden="true"
                />
                Director
            </dt>
            <dd
                class="font-[family-name:var(--font-mono-display)] text-sm text-[--intel-text-primary]"
            >
                {{ movie.director }}
            </dd>
        </div>

        <div
            v-if="movie.writers && movie.writers.length"
            class="flex items-center gap-3 border-b border-[--intel-border] px-5 py-3 last:border-b-0"
        >
            <dt
                class="flex shrink-0 items-center gap-2 text-xs font-semibold tracking-wide text-[--intel-text-muted] uppercase"
            >
                <PenLine
                    class="size-4 text-[--intel-text-muted]"
                    aria-hidden="true"
                />
                {{ movie.writers.length === 1 ? 'Writer' : 'Writers' }}
            </dt>
            <dd
                class="font-[family-name:var(--font-mono-display)] text-sm text-[--intel-text-primary]"
            >
                {{ movie.writers.join(', ') }}
            </dd>
        </div>
    </dl>
</template>
