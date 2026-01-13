<script setup lang="ts">
import type { Movie } from '@/types/models';

import TmdbMovieCard from './TmdbMovieCard.vue';
















interface Props {
    movies: Movie[];
}

defineProps<Props>();

const emit = defineEmits<{
    (e: 'publish', movie: Movie): void;
    (e: 'archive', movie: Movie): void;
}>();
</script>

<template>
    <div class="grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
        <TmdbMovieCard
            v-for="movie in movies"
            :key="movie.id"
            :movie="movie"
            @publish="emit('publish', $event)"
            @archive="emit('archive', $event)"
        />
    </div>
</template>
