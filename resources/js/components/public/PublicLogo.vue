<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface Props {
    showText?: boolean;
    scrolled?: boolean;
}

withDefaults(defineProps<Props>(), {
    showText: false,
    scrolled: false,
});
</script>

<template>
    <Link
        href="/"
        class="flex items-center gap-3 transition-opacity hover:opacity-80"
    >
        <picture
            :class="[
                'transition-all duration-300 hover:scale-105',
                scrolled ? 'h-12 sm:h-14 lg:h-16' : 'h-24 sm:h-28 lg:h-32',
            ]"
        >
            <!-- AVIF format (best compression) -->
            <source
                type="image/avif"
                :srcset="
                    scrolled
                        ? '/images/branding/logo-96.avif 1x, /images/branding/logo-128.avif 2x'
                        : '/images/branding/logo-192.avif 1x, /images/branding/logo-256.avif 2x'
                "
                :sizes="
                    scrolled
                        ? '(max-width: 1024px) 56px, 64px'
                        : '(max-width: 640px) 96px, (max-width: 1024px) 112px, 128px'
                "
            />
            <!-- WebP format (good compression, wider support) -->
            <source
                type="image/webp"
                :srcset="
                    scrolled
                        ? '/images/branding/logo-96.webp 1x, /images/branding/logo-128.webp 2x'
                        : '/images/branding/logo-192.webp 1x, /images/branding/logo-256.webp 2x'
                "
                :sizes="
                    scrolled
                        ? '(max-width: 1024px) 56px, 64px'
                        : '(max-width: 640px) 96px, (max-width: 1024px) 112px, 128px'
                "
            />
            <!-- PNG fallback -->
            <img
                src="/images/branding/logo.png"
                alt="Movies of War"
                class="h-full w-auto"
                loading="eager"
                fetchpriority="high"
                decoding="async"
            />
        </picture>
        <span
            v-if="showText"
            class="hidden text-xl font-bold text-white lg:block"
        >
            Movies of War
        </span>
    </Link>
</template>
