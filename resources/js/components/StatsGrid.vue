<script setup lang="ts">
import StatsCard from './StatsCard.vue';

interface Stat {
    title: string;
    value: string | number;
}

interface Props {
    stats: Stat[];
}

defineProps<Props>();
</script>

<template>
    <div
        class="stats-grid grid grid-cols-1 gap-6"
        style="container-type: inline-size"
    >
        <StatsCard
            v-for="stat in stats"
            :key="stat.title"
            :title="stat.title"
            :value="stat.value"
        />
    </div>
</template>
