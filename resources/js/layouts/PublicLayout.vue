<script setup lang="ts">
import PublicHeader from '@/components/public/PublicHeader.vue';

interface Props {
    canRegister?: boolean;
}

withDefaults(defineProps<Props>(), {
    canRegister: false,
});
</script>

<template>
    <div class="min-h-screen bg-zinc-950">
        <PublicHeader />
        <main class="flex-1">
            <slot />
        </main>
        <footer class="border-t border-zinc-800 bg-zinc-900 py-8">
            <div class="mx-auto max-w-7xl px-4 text-center text-zinc-500 sm:px-6 lg:px-8">
                <p>&copy; 2026 Movies of War. All rights reserved.</p>
            </div>
        </footer>
    </div>
</template>
