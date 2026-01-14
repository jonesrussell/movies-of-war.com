<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { useWindowScroll } from '@vueuse/core';
import { computed } from 'vue';

import PublicContainer from '@/components/public/PublicContainer.vue';
import PublicLogo from '@/components/public/PublicLogo.vue';
import PublicMobileMenu from '@/components/public/PublicMobileMenu.vue';
import PublicNav from '@/components/public/PublicNav.vue';

const page = usePage();
const canRegister = computed(() => {
    const props = page.props as { canRegister?: boolean };
    return props.canRegister ?? false;
});

const { y } = useWindowScroll();
const isScrolled = computed(() => y.value > 20);
</script>

<template>
    <header
        class="sticky top-0 z-50 border-b border-zinc-800 bg-zinc-900/50 backdrop-blur-sm transition-all duration-300"
        :class="{ 'py-2': isScrolled, 'py-4': !isScrolled }"
    >
        <PublicContainer>
            <div class="flex items-center justify-between">
                <PublicLogo :show-text="false" :scrolled="isScrolled" />
                <PublicNav :can-register="canRegister" />
                <PublicMobileMenu :can-register="canRegister" />
            </div>
        </PublicContainer>
    </header>
</template>
