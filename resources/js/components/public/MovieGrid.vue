<script setup lang="ts">
import { cn } from '@/lib/utils';

interface Props {
    dense?: boolean;
    class?: string;
}

withDefaults(defineProps<Props>(), {
    dense: false,
    class: '',
});
</script>

<template>
    <div
        :class="
            cn(
                'movie-grid grid grid-cols-3',
                dense ? 'gap-4' : 'gap-6',
                $props.class,
            )
        "
        style="container-type: inline-size"
    >
        <slot />
    </div>
</template>
