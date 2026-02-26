<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';

defineProps<{
    heroQueue: unknown[];
    pickOfWeekQueue: unknown[];
    movies: unknown[];
}>();
</script>

<template>
    <AppSidebarLayout>
        <Head title="Featured Slot Queue" />
        <div class="p-8">
            <h1 class="text-2xl font-bold text-white">Featured Slot Queue</h1>
            <p class="text-zinc-400">Placeholder — full implementation in Task 10.</p>
        </div>
    </AppSidebarLayout>
</template>
