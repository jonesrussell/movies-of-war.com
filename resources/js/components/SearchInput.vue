<script setup lang="ts">
import { useDebounceFn } from '@vueuse/core';
import { ref, watch } from 'vue';

import { Input } from '@/components/ui/input';

interface Props {
    modelValue?: string;
    placeholder?: string;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Search...',
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const searchValue = ref(props.modelValue || '');

const debouncedEmit = useDebounceFn((value: string) => {
    emit('update:modelValue', value);
}, 300);

watch(searchValue, (value) => {
    debouncedEmit(value);
});

watch(() => props.modelValue, (value) => {
    if (value !== searchValue.value) {
        searchValue.value = value || '';
    }
});
</script>

<template>
    <Input
        v-model="searchValue"
        type="text"
        :placeholder="placeholder"
        class="w-full"
    />
</template>
