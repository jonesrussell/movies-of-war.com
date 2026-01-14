<script setup lang="ts">
import PublicContainer from '@/components/public/PublicContainer.vue';
</script>

<template>
    <footer class="border-t border-zinc-800 bg-zinc-950">
        <PublicContainer class="py-10">
            <div class="flex flex-col items-center gap-3 text-center">
                <p class="text-sm text-zinc-500">
                    &copy; 2026 Movies of War. All rights reserved.
                </p>
                <p class="max-w-2xl text-xs leading-relaxed text-zinc-600">
                    Movie data &amp; images are provided by TMDB. Movies of War
                    is a curated catalog focused on war films across eras,
                    countries, and conflicts.
                </p>
            </div>
        </PublicContainer>
    </footer>
</template>
