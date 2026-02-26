<script setup lang="ts">
import PublicFooter from '@/components/public/PublicFooter.vue';
import PublicHeader from '@/components/public/PublicHeader.vue';
</script>

<template>
    <div class="min-h-screen bg-[--intel-bg-base]">
        <!-- Skip link for keyboard accessibility -->
        <a
            href="#main-content"
            class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:rounded-lg focus:bg-[--intel-accent] focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white focus:shadow-lg"
        >
            Skip to main content
        </a>

        <PublicHeader />
        <main id="main-content" class="flex-1">
            <slot />
        </main>
        <PublicFooter />
    </div>
</template>
