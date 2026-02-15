<script setup lang="ts">
/**
 * Shared badge for upcoming releases. Use on poster (absolute top-right) or inline.
 * Position is left to the parent (e.g. absolute top-3 right-3 for poster overlay).
 */
interface Props {
    /** Compact size for list items / small cards */
    size?: 'default' | 'compact';
}

withDefaults(defineProps<Props>(), {
    size: 'default',
});
</script>

<template>
    <span
        class="inline-flex rounded-full bg-red-600 font-bold tracking-wide text-white uppercase shadow-lg"
        :class="
            size === 'compact'
                ? 'px-2 py-0.5 text-[10px]'
                : 'px-3 py-1 text-xs'
        "
    >
        <slot>Coming Soon</slot>
    </span>
</template>
