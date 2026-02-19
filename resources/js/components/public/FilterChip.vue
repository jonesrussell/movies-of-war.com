<script setup lang="ts">
import { X } from 'lucide-vue-next';

interface Props {
    label: string;
}

defineProps<Props>();

defineEmits<{
    remove: [];
}>();
</script>

<template>
    <button
        type="button"
        class="inline-flex items-center gap-1 rounded-md border border-[--intel-border] bg-[--intel-bg-surface] px-3 py-1.5 text-sm text-[--intel-text-primary] transition hover:border-[--intel-border-bright] focus:outline-none focus-visible:ring-2 focus-visible:ring-[--intel-accent]"
        @click="$emit('remove')"
    >
        <span
            class="max-w-[18rem] truncate font-[family-name:var(--font-mono-display)]"
            >{{ label }}</span
        >
        <X
            class="size-4 text-[--intel-text-muted] transition-colors hover:text-[--intel-alert]"
        />
        <span class="sr-only">Remove</span>
    </button>
</template>
