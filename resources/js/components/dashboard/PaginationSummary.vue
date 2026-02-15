<script setup lang="ts">
import type { PaginationMeta } from '@/types/models';

import { computed } from 'vue';

interface Props {
    meta: PaginationMeta | null | undefined;
}

const props = defineProps<Props>();

const summary = computed(() => {
    const m = props.meta;
    if (!m || m.total === 0) return null;
    const from = m.from ?? 0;
    const to = m.to ?? 0;
    const total = m.total;
    return `Showing ${from}–${to} of ${total}`;
});
</script>

<template>
    <div
        v-if="summary"
        class="mb-2 text-sm text-zinc-400"
    >
        {{ summary }}
    </div>
</template>
