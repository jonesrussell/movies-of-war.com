<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import CoordinateGrid from '@/components/primitives/CoordinateGrid.vue';
import PublicContainer from '@/components/public/PublicContainer.vue';
</script>

<template>
    <footer
        class="relative overflow-hidden border-t border-[--intel-border] bg-[--intel-bg-surface]"
    >
        <CoordinateGrid :opacity="0.02" density="sparse" />

        <PublicContainer class="py-12">
            <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                <!-- Column 1 - Site Info -->
                <div class="flex flex-col gap-3">
                    <span
                        class="font-[family-name:var(--font-mono-display)] text-sm font-semibold tracking-wider text-[--intel-text-primary]"
                    >
                        MOVIES OF WAR
                    </span>
                    <p class="text-sm text-[--intel-text-muted]">
                        A curated intelligence database of war films across
                        eras, countries, and conflicts.
                    </p>
                </div>

                <!-- Column 2 - Quick Links -->
                <div class="flex flex-col gap-3">
                    <span
                        class="font-[family-name:var(--font-mono-display)] text-xs font-semibold tracking-wider text-[--intel-text-muted]"
                    >
                        NAVIGATION
                    </span>
                    <nav class="flex flex-col gap-2">
                        <Link
                            href="/movies"
                            class="text-sm text-[--intel-text-body] hover:text-[--intel-accent]"
                        >
                            Browse Films
                        </Link>
                        <Link
                            href="/articles"
                            class="text-sm text-[--intel-text-body] hover:text-[--intel-accent]"
                        >
                            Articles
                        </Link>
                        <Link
                            href="/watchlist"
                            class="text-sm text-[--intel-text-body] hover:text-[--intel-accent]"
                        >
                            Watchlist
                        </Link>
                    </nav>
                </div>

                <!-- Column 3 - Intel Meta -->
                <div class="flex flex-col gap-3">
                    <span
                        class="font-[family-name:var(--font-mono-display)] text-xs font-semibold tracking-wider text-[--intel-text-muted]"
                    >
                        DATA SOURCE
                    </span>
                    <p class="text-sm text-[--intel-text-muted]">
                        Movie data and images provided by TMDB.
                    </p>
                    <p class="text-xs text-[--intel-text-faint]">
                        Movies of War is not endorsed or certified by TMDB.
                    </p>
                </div>
            </div>
        </PublicContainer>

        <!-- Bottom bar -->
        <div class="border-t border-[--intel-border]">
            <PublicContainer class="py-4">
                <div
                    class="flex flex-col items-center gap-2 md:flex-row md:justify-between"
                >
                    <span
                        class="font-[family-name:var(--font-mono-display)] text-xs text-[--intel-text-faint]"
                    >
                        &copy; 2026 MOVIES OF WAR
                    </span>
                    <span
                        class="font-[family-name:var(--font-mono-display)] text-xs text-[--intel-text-faint]"
                    >
                        MOW-2026 // OPERATIONAL
                    </span>
                </div>
            </PublicContainer>
        </div>
    </footer>
</template>
