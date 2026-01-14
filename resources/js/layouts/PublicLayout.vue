<script setup lang="ts">
import PublicHeader from '@/components/public/PublicHeader.vue';
import PublicFooter from '@/components/public/PublicFooter.vue';

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
