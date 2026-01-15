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
    <div
        class="movie-grid grid grid-cols-2 gap-6"
        style="container-type: inline-size"
    >
        <TmdbMovieCard
            v-for="movie in movies"
            :key="movie.id"
            :movie="movie"
            @publish="emit('publish', $event)"
            @archive="emit('archive', $event)"
        />
    </div>
</template>
