<script setup lang="ts">
import { cn } from '@/lib/utils';

interface Props {
    class?: string;
    animated?: boolean;
}

withDefaults(defineProps<Props>(), {
    class: '',
    animated: false,
});
</script>

<template>
    <div
        :class="
            cn(
                'h-px w-full',
                'bg-gradient-to-r from-transparent via-blue-500/40 to-transparent',
                animated && 'animate-[scan-glow_3s_ease-in-out_infinite]',
                $props.class,
            )
        "
    />
</template>
