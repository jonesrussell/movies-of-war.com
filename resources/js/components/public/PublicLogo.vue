<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface Props {
    showText?: boolean;
    scrolled?: boolean;
}

withDefaults(defineProps<Props>(), {
    showText: false,
    scrolled: false,
});
</script>

<template>
    <Link href="/" class="flex items-center gap-3 transition-opacity hover:opacity-80">
        <img
            src="/images/branding/logo.png"
            alt="Movies of War"
            :class="[
                'transition-all duration-300 hover:scale-105',
                scrolled ? 'h-12 sm:h-14 lg:h-16' : 'h-24 sm:h-28 lg:h-32',
            ]"
        />
        <span
            v-if="showText"
            class="hidden text-xl font-bold text-white lg:block"
        >
            Movies of War
        </span>
    </Link>
</template>
