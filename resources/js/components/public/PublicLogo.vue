<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface Props {
    showText?: boolean;
}

withDefaults(defineProps<Props>(), {
    showText: false,
});
</script>

<template>
    <Link href="/" class="flex items-center gap-3 transition-opacity hover:opacity-80">
        <img
            src="/images/branding/logo.png"
            alt="Movies of War"
            class="h-10 sm:h-11 lg:h-12 transition-transform hover:scale-105"
        />
        <span
            v-if="showText"
            class="hidden text-xl font-bold text-white lg:block"
        >
            Movies of War
        </span>
    </Link>
</template>
