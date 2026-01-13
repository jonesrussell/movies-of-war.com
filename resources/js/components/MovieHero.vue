<script setup lang="ts">















import { Link } from '@inertiajs/vue3'
import { Play, Plus, Info } from 'lucide-vue-next'
import type { Movie } from '@/types'

interface Props {
  movie: Movie
  title?: string
  subtitle?: string
}

const props = defineProps<Props>()

const posterImage = props.movie.poster_url || '/images/placeholders/poster-placeholder.png'
</script>

<template>
  <div class="relative min-h-[70vh] overflow-hidden bg-zinc-950 py-6 sm:py-12">
    <div class="absolute inset-0">
      <img
        :src="posterImage"
        :alt="movie.title"
        class="h-full w-full object-cover opacity-30 blur-sm"
      />
      <div class="absolute inset-0 bg-gradient-to-r from-zinc-950 via-zinc-950/80 to-transparent" />
      <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-transparent to-transparent" />
    </div>

    <div class="relative mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8 lg:py-20">
      <div class="grid gap-8 lg:grid-cols-12">
        <div class="lg:col-span-4">
          <img
            :src="posterImage"
            :alt="movie.title"
            class="w-full rounded-lg shadow-2xl"
          />
        </div>

        <div class="flex flex-col justify-center lg:col-span-8 lg:pr-8">
          <div v-if="subtitle" class="mb-2 text-sm font-semibold uppercase tracking-wider text-red-500">
            {{ subtitle }}
          </div>

          <h1 class="mb-4 text-5xl font-bold text-white lg:text-6xl">
            {{ movie.title }}
          </h1>

          <div class="mb-6 flex flex-wrap items-center gap-4 text-sm text-zinc-300">
            <span class="font-semibold">{{ movie.release_year }}</span>
            <span v-if="movie.runtime">{{ movie.runtime }} min</span>
            <span v-if="movie.conflict" class="rounded bg-zinc-800 px-2 py-1 text-xs font-semibold">
              {{ movie.conflict }}
            </span>
          </div>

          <p class="mb-8 text-lg leading-relaxed text-zinc-300">
            {{ movie.synopsis }}
          </p>

          <div v-if="movie.tags && movie.tags.length > 0" class="mb-8 flex flex-wrap gap-2">
            <span
              v-for="tag in movie.tags"
              :key="tag.id"
              class="rounded-full bg-zinc-800/80 px-3 py-1 text-sm text-zinc-300"
            >
              {{ tag.name }}
            </span>
          </div>

          <div class="flex flex-wrap gap-4">
            <Link
              :href="`/movies/${movie.slug}`"
              class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-6 py-3 font-semibold text-white transition-colors hover:bg-red-700"
            >
              <Info class="size-5" />
              More Details
            </Link>

            <a
              v-if="movie.trailer_url"
              :href="movie.trailer_url"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center gap-2 rounded-lg bg-zinc-800 px-6 py-3 font-semibold text-white transition-colors hover:bg-zinc-700"
            >
              <Play class="size-5" />
              Watch Trailer
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
