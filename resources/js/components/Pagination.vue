<script setup lang="ts">
import type { PaginationMeta } from '@/types/models';

import { Link } from '@inertiajs/vue3';
















interface Props {
    meta: PaginationMeta;
}

defineProps<Props>();
</script>

<template>
    <div v-if="meta.last_page > 1" class="mt-6 flex justify-center gap-2">
        <Link
            v-for="link in meta.links"
            :key="link.label"
            :href="link.url || '#'"
            :class="[
                'rounded px-3 py-2 text-sm transition-colors',
                link.active
                    ? 'bg-primary text-primary-foreground'
                    : link.url
                    ? 'bg-secondary text-secondary-foreground hover:bg-secondary/80'
                    : 'bg-muted text-muted-foreground cursor-not-allowed pointer-events-none',
            ]"
        >
            <!-- eslint-disable-next-line vue/no-v-html -->
            <span v-html="link.label" />
        </Link>
    </div>
</template>
