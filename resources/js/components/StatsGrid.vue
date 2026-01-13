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
    <div class="grid gap-6 sm:grid-cols-3">
        <StatsCard
            v-for="stat in stats"
            :key="stat.title"
            :title="stat.title"
            :value="stat.value"
        />
    </div>
</template>
