<script setup lang="ts">
import { computed } from 'vue';

import { cn } from '@/lib/utils';

type GridDensity = 'sparse' | 'normal' | 'dense';

interface Props {
    density?: GridDensity;
    opacity?: number;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    density: 'normal',
    opacity: 0.04,
    class: '',
});

const gridSize = computed(
    () =>
        ({
            sparse: 60,
            normal: 40,
            dense: 24,
        })[props.density],
);
</script>

<template>
    <div
        :class="cn('pointer-events-none absolute inset-0', $props.class)"
        :style="{
            backgroundImage: `
                linear-gradient(to right, rgba(59, 130, 246, ${opacity}) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(59, 130, 246, ${opacity}) 1px, transparent 1px)
            `,
            backgroundSize: `${gridSize}px ${gridSize}px`,
        }"
    />
</template>
