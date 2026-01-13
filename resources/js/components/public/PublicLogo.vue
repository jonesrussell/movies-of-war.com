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
            class="h-24 sm:h-28 lg:h-32 transition-transform hover:scale-105"
        />
        <span
            v-if="showText"
            class="hidden text-xl font-bold text-white lg:block"
        >
            Movies of War
        </span>
    </Link>
</template>
