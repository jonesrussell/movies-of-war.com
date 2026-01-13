<script setup lang="ts">
import type { BreadcrumbItemType } from '@/types';
import { computed } from 'vue';

import AppLayout from '@/layouts/app/AppSidebarLayout.vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const layoutProps = computed(() => {
    const result: { breadcrumbs?: BreadcrumbItemType[] } = {};
    if (props.breadcrumbs !== undefined) {
        result.breadcrumbs = props.breadcrumbs;
    }
    return result;
});
</script>

<template>
    <AppLayout v-bind="layoutProps">
        <slot />
    </AppLayout>
</template>
