<script setup lang="ts">
import { computed } from 'vue';

import { cn } from '@/lib/utils';

type Direction = 'to-t' | 'to-b' | 'to-l' | 'to-r' | 'to-tr' | 'to-br';
type Intensity = 'light' | 'medium' | 'heavy';

interface Props {
    direction?: Direction;
    intensity?: Intensity;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    direction: 'to-t',
    intensity: 'medium',
    class: '',
});

const directionClass = computed(
    () =>
        ({
            'to-t': 'bg-gradient-to-t',
            'to-b': 'bg-gradient-to-b',
            'to-l': 'bg-gradient-to-l',
            'to-r': 'bg-gradient-to-r',
            'to-tr':
                'bg-[linear-gradient(to_top_right,var(--tw-gradient-stops))]',
            'to-br':
                'bg-[linear-gradient(to_bottom_right,var(--tw-gradient-stops))]',
        })[props.direction],
);

const intensityClass = computed(
    () =>
        ({
            light: 'from-black/60 via-black/20 to-transparent',
            medium: 'from-black/90 via-black/40 to-transparent',
            heavy: 'from-zinc-950 via-zinc-950/80 to-transparent',
        })[props.intensity],
);
</script>

<template>
    <div
        :class="
            cn(
                'pointer-events-none absolute inset-0',
                directionClass,
                intensityClass,
                $props.class,
            )
        "
    />
</template>
