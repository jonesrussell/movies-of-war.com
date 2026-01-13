<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import type { Movie } from '@/types'
import MovieHero from '@/components/MovieHero.vue'
import FeaturedMovie from '@/components/FeaturedMovie.vue'
import MovieCard from '@/components/MovieCard.vue'

interface Props {
  canRegister: boolean
  heroMovie?: Movie
  pickOfWeekMovie?: Movie
  latestMovies: Movie[]
}

defineProps<Props>()

const page = usePage()
const auth = page.props.auth as { user: any }
</script>

<template>
  <Head title="Movies of War - Curated War Films Database" />

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

            <template v-if="auth.user">
              <Link
                href="/watchlist"
                class="text-zinc-300 transition-colors hover:text-white"
              >
                Watchlist
              </Link>
              <Link
                href="/dashboard"
                class="rounded-lg bg-red-600 px-4 py-2 font-semibold text-white transition-colors hover:bg-red-700"
              >
                Dashboard
              </Link>
            </template>

            <template v-else>
              <Link
                href="/login"
                class="text-zinc-300 transition-colors hover:text-white"
              >
                Login
              </Link>
              <Link
                v-if="canRegister"
                href="/register"
                class="rounded-lg bg-red-600 px-4 py-2 font-semibold text-white transition-colors hover:bg-red-700"
              >
                Register
              </Link>
            </template>
          </nav>
        </div>
      </div>
    </header>

    <MovieHero v-if="heroMovie" :movie="heroMovie" subtitle="Featured Upcoming Release" />

    <div v-else class="bg-zinc-900 py-20">
      <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
        <h1 class="text-5xl font-bold text-white">Movies of War</h1>
        <p class="mt-4 text-xl text-zinc-400">
          A curated database of war films, documentaries, and related media
        </p>
      </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
      <div v-if="pickOfWeekMovie" class="mx-auto mb-12 max-w-4xl">
        <FeaturedMovie :movie="pickOfWeekMovie" title="Pick of the Week" />
      </div>

      <div>
        <div class="mb-8 flex items-center justify-between">
          <h2 class="text-3xl font-bold text-white">Latest Releases</h2>
          <Link
            href="/movies"
            class="text-red-500 transition-colors hover:text-red-400"
          >
            View All →
          </Link>
        </div>

        <div class="grid grid-cols-2 gap-6 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
          <MovieCard v-for="movie in latestMovies" :key="movie.id" :movie="movie" />
        </div>
      </div>

      <div class="mt-16 rounded-lg bg-zinc-900 p-8 text-center">
        <h3 class="mb-4 text-2xl font-bold text-white">
          Explore Our Curated Collection
        </h3>
        <p class="mb-6 text-zinc-400">
          Discover 30+ carefully selected war films spanning WWI, WWII, Vietnam, and modern conflicts
        </p>
        <Link
          href="/movies"
          class="inline-block rounded-lg bg-red-600 px-6 py-3 font-semibold text-white transition-colors hover:bg-red-700"
        >
          Browse All Movies
        </Link>
      </div>
    </div>

    <footer class="border-t border-zinc-800 bg-zinc-900 py-8">
      <div class="mx-auto max-w-7xl px-4 text-center text-zinc-500 sm:px-6 lg:px-8">
        <p>&copy; 2026 Movies of War. All rights reserved.</p>
      </div>
    </footer>
  </div>
</template>
