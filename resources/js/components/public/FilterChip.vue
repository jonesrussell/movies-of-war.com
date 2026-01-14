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
        class="inline-flex items-center gap-1 rounded-full bg-zinc-900 px-3 py-1.5 text-sm text-zinc-200 ring-1 ring-zinc-800/70 transition hover:bg-zinc-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
        @click="$emit('remove')"
    >
        <span class="max-w-[18rem] truncate">{{ label }}</span>
        <X class="size-4 text-zinc-400" />
        <span class="sr-only">Remove</span>
    </button>
</template>
