<script setup lang="ts">
import { Check } from 'lucide-vue-next';

interface Props {
    content: string;
    mediaUrls?: string[];
    /** Optional overlay text on media (e.g. movie title) */
    mediaOverlayText?: string | null;
    /** Compact mode for use in tables */
    compact?: boolean;
}

withDefaults(defineProps<Props>(), {
    mediaUrls: () => [],
    mediaOverlayText: null,
    compact: false,
});

const siteLabel = 'movies-of-war.com';
</script>

<template>
    <div
        :class="[
            'rounded-xl border border-zinc-800 bg-black text-white shadow-lg',
            compact ? 'p-3' : 'p-4',
        ]"
    >
        <!-- Header: avatar + name + handle -->
        <div class="mb-2 flex items-start gap-3">
            <div
                class="size-10 shrink-0 overflow-hidden rounded-full bg-zinc-700 ring-1 ring-zinc-600"
            >
                <img
                    src="/images/branding/logo-96.webp"
                    alt=""
                    class="size-full object-cover"
                    loading="lazy"
                />
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-1.5">
                    <span class="font-bold text-white">Movies of War</span>
                    <Check
                        class="size-4 shrink-0 text-blue-400"
                        aria-label="Verified"
                    />
                </div>
                <p class="text-sm text-zinc-500">@MoviesOfWar</p>
            </div>
        </div>

        <!-- Content -->
        <div
            :class="[
                'whitespace-pre-wrap break-words text-[15px] leading-5 text-white',
                compact ? 'line-clamp-3' : '',
            ]"
        >
            {{ content || '(No content)' }}
        </div>

        <!-- Media with optional overlay -->
        <div
            v-if="mediaUrls && mediaUrls.length > 0"
            class="relative mt-3 overflow-hidden rounded-2xl border border-zinc-800"
        >
            <div class="aspect-video w-full bg-zinc-900">
                <img
                    v-if="mediaUrls[0]"
                    :src="mediaUrls[0]"
                    alt=""
                    class="size-full object-cover"
                    loading="lazy"
                    @error="
                        ($event.target as HTMLImageElement).style.display =
                            'none'
                    "
                />
            </div>
            <div
                v-if="mediaOverlayText"
                class="absolute bottom-0 left-0 right-0 bg-black/70 px-3 py-2 text-sm font-medium text-white"
            >
                {{ mediaOverlayText }}
            </div>
        </div>

        <!-- Source -->
        <p class="mt-2 text-xs text-zinc-500">
            From {{ siteLabel }}
        </p>
    </div>
</template>
