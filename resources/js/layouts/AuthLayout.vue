<script setup lang="ts">
import { computed } from 'vue';

import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';

const props = defineProps<{
    title?: string;
    description?: string;
}>();

const layoutProps = computed(() => {
    const result: { title?: string; description?: string } = {};
    if (props.title !== undefined) {
        result.title = props.title;
    }
    if (props.description !== undefined) {
        result.description = props.description;
    }
    return result;
});
</script>

<template>
    <AuthLayout v-bind="layoutProps">
        <slot />
    </AuthLayout>
</template>
