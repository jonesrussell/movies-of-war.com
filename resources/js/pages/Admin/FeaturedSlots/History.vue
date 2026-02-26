<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';

defineProps<{
    history: {
        data: unknown[];
        meta: unknown;
    };
    queryParams: Record<string, string | undefined>;
}>();
</script>

<template>
    <AppSidebarLayout>
        <Head title="Featured Slot History" />
        <div class="p-8">
            <h1 class="text-2xl font-bold text-white">Featured Slot History</h1>
            <p class="text-zinc-400">Placeholder — full implementation in Task 11.</p>
        </div>
    </AppSidebarLayout>
</template>
