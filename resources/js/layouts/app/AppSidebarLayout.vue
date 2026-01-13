<script setup lang="ts">
import type { BreadcrumbItemType } from '@/types';

import { computed } from 'vue';

import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const headerProps = computed(() => {
    const result: { breadcrumbs?: BreadcrumbItemType[] } = {};
    if (props.breadcrumbs !== undefined) {
        result.breadcrumbs = props.breadcrumbs;
    }
    return result;
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader v-bind="headerProps" />
            <slot />
        </AppContent>
    </AppShell>
</template>
