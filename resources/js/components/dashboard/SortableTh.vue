<script setup lang="ts">
import { ChevronDown, ChevronUp } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    label: string;
    sortKey: string;
    currentSort: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    sort: [column: string];
}>();

const direction = computed<'asc' | 'desc' | null>(() => {
    if (props.currentSort === `${props.sortKey}_asc`) return 'asc';
    if (props.currentSort === `${props.sortKey}_desc`) return 'desc';
    return null;
});

function handleClick() {
    emit('sort', props.sortKey);
}
</script>

<template>
    <th
        class="px-6 py-3 text-left text-xs font-medium tracking-wider text-zinc-400 uppercase"
    >
        <button
            type="button"
            class="flex items-center gap-1 hover:text-white"
            @click="handleClick"
        >
            {{ label }}
            <ChevronUp
                v-if="direction === 'asc'"
                class="size-4"
            />
            <ChevronDown
                v-else-if="direction === 'desc'"
                class="size-4"
            />
        </button>
    </th>
</template>
