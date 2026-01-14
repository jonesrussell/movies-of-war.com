<script setup lang="ts">
import { computed } from 'vue';

import { cn } from '@/lib/utils';

type PatternSize = 'sm' | 'md' | 'lg';

interface Props {
    size?: PatternSize;
    opacity?: number;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    opacity: 0.06,
    class: '',
});

const sizeValue = computed(
    () =>
        ({
            sm: '14px 14px',
            md: '18px 18px',
            lg: '22px 22px',
        })[props.size],
);
</script>

<template>
    <div
        :class="cn('pointer-events-none absolute inset-0', $props.class)"
        :style="{
            backgroundImage:
                'radial-gradient(circle at 1px 1px, white 1px, transparent 0)',
            backgroundSize: sizeValue,
            opacity: opacity,
        }"
    />
</template>
