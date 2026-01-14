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
                'grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6',
                dense ? 'gap-4' : 'gap-6',
                $props.class,
            )
        "
    >
        <slot />
    </div>
</template>
