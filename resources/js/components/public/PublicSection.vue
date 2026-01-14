<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed } from 'vue';

type Spacing = 'none' | 'sm' | 'md' | 'lg';

interface Props {
    as?: 'section' | 'div';
    spacing?: Spacing;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    as: 'section',
    spacing: 'md',
    class: '',
});

const spacingClass: Record<Spacing, string> = {
    none: '',
    sm: 'py-8 sm:py-10',
    md: 'py-10 sm:py-14',
    lg: 'py-14 sm:py-18 lg:py-20',
};

const resolvedSpacing = computed<Spacing>(() => props.spacing ?? 'md');
</script>

<template>
    <component
        :is="as"
        :class="cn(spacingClass[resolvedSpacing], $props.class)"
    >
        <slot />
    </component>
</template>
