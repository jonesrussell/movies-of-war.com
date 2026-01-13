<script setup lang="ts">
import { useDebounceFn } from '@vueuse/core';
import { computed, ref, watch } from 'vue';

import { Input } from '@/components/ui/input';

interface Props {
    modelValue?: string;
    placeholder?: string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: undefined,
    placeholder: 'Search...',
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const modelValue = computed(() => props.modelValue);
const searchValue = ref(modelValue.value || '');

const debouncedEmit = useDebounceFn((value: string) => {
    emit('update:modelValue', value);
}, 300);

watch(searchValue, (value) => {
    void debouncedEmit(value);
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
