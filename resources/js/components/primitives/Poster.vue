<script setup lang="ts">
import { computed, ref } from 'vue';

import { cn } from '@/lib/utils';

type AspectRatio = '2/3' | '16/9' | '4/3' | '1/1';
type LoadingStrategy = 'lazy' | 'eager';

interface Props {
    src: string | null;
    alt: string;
    aspectRatio?: AspectRatio;
    loading?: LoadingStrategy;
    priority?: boolean;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    aspectRatio: '2/3',
    loading: 'lazy',
    priority: false,
    class: '',
});

const isLoaded = ref(false);
const hasError = ref(false);

const imageSrc = computed(
    () => props.src || '/images/placeholders/poster-placeholder.png',
);

const aspectClass = computed(
    () =>
        ({
            '2/3': 'aspect-[2/3]',
            '16/9': 'aspect-video',
            '4/3': 'aspect-[4/3]',
            '1/1': 'aspect-square',
        })[props.aspectRatio],
);

const loadingAttr = computed(() => (props.priority ? 'eager' : props.loading));
</script>

<template>
    <div
        :class="
            cn(
                'relative overflow-hidden bg-zinc-900',
                aspectClass,
                $props.class,
            )
        "
    >
        <!-- Skeleton placeholder -->
        <div
            v-if="!isLoaded && !hasError"
            class="absolute inset-0 animate-pulse bg-zinc-800"
        />

        <!-- Main image -->
        <img
            :src="imageSrc"
            :alt="alt"
            :loading="loadingAttr"
            :fetchpriority="priority ? 'high' : undefined"
            decoding="async"
            class="h-full w-full object-cover transition-opacity duration-500"
            :class="isLoaded ? 'opacity-100' : 'opacity-0'"
            @load="isLoaded = true"
            @error="hasError = true"
        />

        <!-- Slot for overlays -->
        <slot />
    </div>
</template>
