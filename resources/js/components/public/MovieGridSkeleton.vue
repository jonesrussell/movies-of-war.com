<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton';
import MovieGrid from '@/components/public/MovieGrid.vue';

withDefaults(
    defineProps<{
        count?: number;
    }>(),
    { count: 18 },
);
</script>

<template>
    <MovieGrid>
        <div v-for="i in count" :key="i" class="flex flex-col gap-2">
            <Skeleton class="aspect-[2/3] w-full rounded-xl" />
            <Skeleton class="h-4 w-4/5 rounded" />
            <Skeleton class="h-3 w-2/3 rounded" />
        </div>
    </MovieGrid>
</template>
