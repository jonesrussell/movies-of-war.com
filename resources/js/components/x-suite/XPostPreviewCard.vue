<script setup lang="ts">
interface Props {
    content: string;
    mediaUrls?: string[];
    mediaOverlayText?: string | null;
    compact?: boolean;
}

withDefaults(defineProps<Props>(), {
    mediaUrls: () => [],
    mediaOverlayText: null,
    compact: false,
});
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
            />
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-1.5">
                    <span class="font-bold text-white">Your Account</span>
                </div>
                <p class="text-sm text-zinc-500">@your_handle</p>
            </div>
        </div>

        <!-- Content -->
        <div
            :class="[
                'text-[15px] leading-5 break-words whitespace-pre-wrap text-white',
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
                class="absolute right-0 bottom-0 left-0 bg-black/70 px-3 py-2 text-sm font-medium text-white"
            >
                {{ mediaOverlayText }}
            </div>
        </div>
    </div>
</template>
