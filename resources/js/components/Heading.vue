<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    title: string;
    description?: string;
    size?: 'sm' | 'md' | 'lg';
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
});

const headingClasses = {
    sm: 'mb-0.5 text-base font-medium',
    md: 'mb-0.5 text-base font-medium',
    lg: 'text-xl font-semibold tracking-tight',
};

const wrapperClasses = {
    sm: '',
    md: '',
    lg: 'mb-8 space-y-0.5',
};

const headingTag = computed(() => (props.size === 'lg' ? 'h2' : 'h3'));
const wrapperTag = computed(() => (props.size === 'lg' ? 'div' : 'header'));
</script>

<template>
    <component :is="wrapperTag" :class="wrapperClasses[size]">
        <component :is="headingTag" :class="headingClasses[size]">{{
            title
        }}</component>
        <p v-if="description" class="text-sm text-muted-foreground">
            {{ description }}
        </p>
    </component>
</template>
