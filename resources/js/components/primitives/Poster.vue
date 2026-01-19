<script setup lang="ts">
import { computed, ref, watch } from 'vue';

import { cn } from '@/lib/utils';
import {
    extractPosterPath,
    getLocalPosterSrcset,
    getLocalPosterUrl,
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

// Reset loaded state when src changes
watch(
    () => props.src,
    () => {
        isLoaded.value = false;
        hasError.value = false;
    },
);

// Ensure image loads even if there's an error with responsive images
watch(hasError, (error) => {
    if (error) {
        // If there's an error, try to load the fallback
        isLoaded.value = true;
    }
});

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

// Check if we have a local poster path
const localPosterPath = computed(() => {
    if (!props.posterPath) {
        return null;
    }

    // Check if it's a local path (starts with 'posters/' or contains '/storage/posters/')
    if (
        props.posterPath.startsWith('posters/') ||
        props.posterPath.includes('/storage/posters/')
    ) {
        // Normalize to 'posters/filename.ext' format
        const match = props.posterPath.match(/posters\/(.+)$/);
        return match ? `posters/${match[1]}` : null;
    }

    return null;
});

// Extract TMDB poster path for fallback
const tmdbPosterPath = computed(() => {
    // If we have a local path, don't use TMDB
    if (localPosterPath.value) {
        return null;
    }

    // If posterPath is provided and it's a TMDB path format
    if (props.posterPath) {
        if (
            props.posterPath.startsWith('/') &&
            !props.posterPath.startsWith('/posters/') &&
            !props.posterPath.startsWith('/storage/')
        ) {
            return props.posterPath;
        }
    }

    // Try to extract from poster_url if it's a TMDB URL
    if (props.src && isTmdbImageUrl(props.src)) {
        const extracted = extractPosterPath(props.src);
        if (extracted && !extracted.startsWith('posters/')) {
            return extracted;
        }
    }

    return null;
});

// Generate srcsets for local files (AVIF, WebP, original)
const avifSrcset = computed(() => {
    if (!localPosterPath.value) {
        return '';
    }

    return getLocalPosterSrcset(localPosterPath.value, props.context, 'avif');
});

const webpSrcset = computed(() => {
    if (localPosterPath.value) {
        return getLocalPosterSrcset(
            localPosterPath.value,
            props.context,
            'webp',
        );
    }

    if (tmdbPosterPath.value) {
        return getTmdbPosterSrcset(tmdbPosterPath.value, props.context, true);
    }

    return '';
});

const originalSrcset = computed(() => {
    if (localPosterPath.value) {
        return getLocalPosterSrcset(
            localPosterPath.value,
            props.context,
            'original',
        );
    }

    if (tmdbPosterPath.value) {
        return getTmdbPosterSrcset(tmdbPosterPath.value, props.context, false);
    }

    return '';
});

// Get fallback image URL
const fallbackImageSrc = computed(() => {
    if (localPosterPath.value) {
        // Use 500px width as default fallback for local files
        const sizes = {
            grid: 342,
            detail: 500,
            hero: 780,
        };
        const width = sizes[props.context] || 500;
        return getLocalPosterUrl(localPosterPath.value, width, 'original');
    }

    return props.src || '/images/placeholders/poster-placeholder.png';
});

const sizesAttr = computed(() => {
    return getTmdbPosterSizes(props.context);
});

// Determine if we should use responsive images
const useResponsiveImages = computed(() => {
    const hasLocalFiles = Boolean(
        localPosterPath.value &&
        (avifSrcset.value || webpSrcset.value || originalSrcset.value),
    );

    const hasTmdbFiles = Boolean(
        tmdbPosterPath.value && (webpSrcset.value || originalSrcset.value),
    );

    return hasLocalFiles || hasTmdbFiles;
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
            <!-- AVIF format (best compression) - only for local files -->
            <source
                v-if="avifSrcset"
                type="image/avif"
                :srcset="avifSrcset"
                :sizes="sizesAttr"
            />
            <!-- WebP format (good compression, wider support) -->
            <source
                v-if="webpSrcset"
                type="image/webp"
                :srcset="webpSrcset"
                :sizes="sizesAttr"
            />
            <!-- Original format (JPEG/PNG) fallback -->
            <source
                v-if="originalSrcset"
                :type="localPosterPath ? 'image/jpeg' : 'image/jpeg'"
                :srcset="originalSrcset"
                :sizes="sizesAttr"
            />
            <!-- Fallback img -->
            <img
                :src="fallbackImageSrc"
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
        <!-- Standard img for non-responsive images -->
        <img
            v-else
            :src="fallbackImageSrc"
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
