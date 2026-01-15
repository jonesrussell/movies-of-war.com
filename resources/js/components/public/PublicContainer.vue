<script setup lang="ts">
import { cn } from '@/lib/utils';

interface Props {
    as?: 'div' | 'section' | 'header' | 'main' | 'footer';
    class?: string;
}

withDefaults(defineProps<Props>(), {
    as: 'div',
    class: '',
});
</script>

<template>
    <component
        :is="as"
        :class="cn('mx-auto w-full px-4 sm:px-6 lg:px-8', $props.class)"
        style="max-width: min(1280px, 100vw - 2rem)"
    >
        <slot />
    </component>
</template>
