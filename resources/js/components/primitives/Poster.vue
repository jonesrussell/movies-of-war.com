<script setup lang="ts">
import { computed, ref } from 'vue';

import { cn } from '@/lib/utils';
import {
    extractPosterPath,
    getTmdbPosterSizes,
    getTmdbPosterSrcset,
    isTmdbImageUrl,
} from '@/utils/image';

type AspectRatio = '2/3' | '16/9' | '4/3' | '1/1';
type LoadingStrategy = 'lazy' | 'eager';
type ImageContext = 'grid' | 'detail' | 'hero';

interface Props {
    src: string | null;
    alt: string;
    aspectRatio?: AspectRatio;
    loading?: LoadingStrategy;
    priority?: boolean;
    context?: ImageContext;
    posterPath?: string | null;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    aspectRatio: '2/3',
    loading: 'lazy',
    priority: false,
    context: 'grid',
    posterPath: null,
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

// Extract poster path for TMDB images
const tmdbPosterPath = computed(() => {
    if (props.posterPath) {
        return props.posterPath;
    }

    if (isTmdbImageUrl(props.src)) {
        return extractPosterPath(props.src);
    }

    return null;
});

// Generate srcsets for responsive images
const webpSrcset = computed(() => {
    if (!tmdbPosterPath.value) {
        return '';
    }

    return getTmdbPosterSrcset(tmdbPosterPath.value, props.context, true);
});

const jpegSrcset = computed(() => {
    if (!tmdbPosterPath.value) {
        return '';
    }

    return getTmdbPosterSrcset(tmdbPosterPath.value, props.context, false);
});

const sizesAttr = computed(() => {
    if (!tmdbPosterPath.value) {
        return undefined;
    }

    return getTmdbPosterSizes(props.context);
});

// Determine if we should use responsive images
const useResponsiveImages = computed(() => {
    return Boolean(
        tmdbPosterPath.value && (webpSrcset.value || jpegSrcset.value),
    );
});
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

        <!-- Main image with responsive support -->
        <picture v-if="useResponsiveImages">
            <!-- WebP source with srcset -->
            <source
                v-if="webpSrcset"
                type="image/webp"
                :srcset="webpSrcset"
                :sizes="sizesAttr"
            />
            <!-- JPEG fallback with srcset -->
            <source
                v-if="jpegSrcset"
                type="image/jpeg"
                :srcset="jpegSrcset"
                :sizes="sizesAttr"
            />
            <!-- Fallback img -->
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
        </picture>
        <!-- Standard img for non-TMDB images -->
        <img
            v-else
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
