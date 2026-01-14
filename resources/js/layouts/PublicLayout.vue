<script setup lang="ts">
import PublicFooter from '@/components/public/PublicFooter.vue';
import PublicHeader from '@/components/public/PublicHeader.vue';
</script>

<template>
    <div class="min-h-screen bg-zinc-950">
        <PublicHeader />
        <main class="flex-1">
            <slot />
        </main>
        <PublicFooter />
    </div>
</template>
