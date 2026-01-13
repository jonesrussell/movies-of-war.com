<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { Bookmark } from 'lucide-vue-next'
import type { Movie } from '@/types'
import MovieCard from '@/components/MovieCard.vue'

interface Props {
  movies: Movie[]
}

defineProps<Props>()
const page = usePage()
const auth = page.props.auth as { user: any }
</script>

<template>
  <Head title="My Watchlist - Movies of War" />

  <div class="min-h-screen bg-zinc-950">
    <header class="border-b border-zinc-800 bg-zinc-900/50 backdrop-blur-sm">
      <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between">
          <Link href="/" class="flex items-center">
            <img src="/images/branding/logo.png" alt="Movies of War" class="h-8" />
          </Link>

          <nav class="flex items-center gap-6">
            <Link
              href="/movies"
              class="text-zinc-300 transition-colors hover:text-white"
            >
              Browse Movies
            </Link>

            <Link
              href="/watchlist"
              class="text-red-500"
            >
              Watchlist
            </Link>


            <Link
              href="/dashboard"
              class="rounded-lg bg-red-600 px-4 py-2 font-semibold text-white transition-colors hover:bg-red-700"
            >
              Dashboard
            </Link>
          </nav>
        </div>
      </div>
    </header>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <div class="mb-8">
        <div class="flex items-center gap-3">
          <Bookmark class="size-8 text-red-500" />
          <h1 class="text-4xl font-bold text-white">My Watchlist</h1>
        </div>
        <p class="mt-2 text-zinc-400">
          {{ movies.length }} {{ movies.length === 1 ? 'film' : 'films' }} saved
        </p>
      </div>

      <div v-if="movies.length > 0">
        <div class="grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
          <MovieCard v-for="movie in movies" :key="movie.id" :movie="movie" />
        </div>
      </div>

      <div v-else class="rounded-lg border border-zinc-800 bg-zinc-900 py-16 text-center">
        <img
          src="/images/illustrations/watchlist-placeholder.png"
          alt="Empty watchlist"
          class="mx-auto mb-6 h-32 w-32 opacity-50"
        />
        <h2 class="mb-2 text-xl font-bold text-white">Your watchlist is empty</h2>
        <p class="mb-6 text-zinc-400">
          Start adding films you want to watch
        </p>
        <Link
          href="/movies"
          class="inline-block rounded-lg bg-red-600 px-6 py-3 font-semibold text-white transition-colors hover:bg-red-700"
        >
          Browse Movies
        </Link>
      </div>
    </div>

    <footer class="border-t border-zinc-800 bg-zinc-900 py-8 mt-16">
      <div class="mx-auto max-w-7xl px-4 text-center text-zinc-500 sm:px-6 lg:px-8">
        <p>&copy; 2026 Movies of War. All rights reserved.</p>
      </div>
    </footer>
  </div>
</template>
