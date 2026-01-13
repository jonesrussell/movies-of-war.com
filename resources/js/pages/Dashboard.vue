<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { CheckCircle, Archive } from 'lucide-vue-next'
import type { Movie, PaginatedMovies } from '@/types/models'

interface Props {
  stats: {
    movies: number
    tags: number
    activeFeatures: number
  }
  tmdbDrafts: PaginatedMovies | []
  queryParams: {
    search?: string
  }
}

const props = defineProps<Props>()
const page = usePage()
const auth = page.props.auth as { user: any }

const search = ref(props.queryParams.search || '')

watch(search, (value) => {
  router.get('/dashboard', {
    search: value || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}, { debounce: 300 })

function publishMovie(movie: Movie) {
  if (confirm(`Publish "${movie.title}"?`)) {
    router.post(`/movies/${movie.id}/publish`, {}, {
      preserveScroll: true,
    })
  }
}

function archiveMovie(movie: Movie) {
  if (confirm(`Archive "${movie.title}"?`)) {
    router.post(`/movies/${movie.id}/archive`, {}, {
      preserveScroll: true,
    })
  }
}
</script>

<template>
  <Head title="Dashboard - Movies of War" />

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
              class="text-zinc-300 transition-colors hover:text-white"
            >
              Watchlist
            </Link>

            <Link
              href="/dashboard"
              class="text-red-500"
            >
              Dashboard
            </Link>
          </nav>
        </div>
      </div>
    </header>

    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
      <!-- Stats Grid -->
      <div class="mb-8 grid gap-6 sm:grid-cols-3">
        <div class="rounded-lg bg-zinc-900 p-6">
          <div class="text-sm font-medium text-zinc-400">Published Movies</div>
          <div class="mt-2 text-3xl font-bold text-white">{{ stats.movies }}</div>
        </div>

        <div class="rounded-lg bg-zinc-900 p-6">
          <div class="text-sm font-medium text-zinc-400">Total Tags</div>
          <div class="mt-2 text-3xl font-bold text-white">{{ stats.tags }}</div>
        </div>

        <div class="rounded-lg bg-zinc-900 p-6">
          <div class="text-sm font-medium text-zinc-400">Active Features</div>
          <div class="mt-2 text-3xl font-bold text-white">{{ stats.activeFeatures }}</div>
        </div>
      </div>

      <!-- Admin TMDB Section -->
      <div v-if="auth.user?.is_admin && Array.isArray(tmdbDrafts.data)" class="mb-8">
        <div class="mb-6 flex items-center justify-between">
          <div>
            <h2 class="text-2xl font-bold text-white">TMDB Imports</h2>
            <p class="mt-1 text-sm text-zinc-400">
              {{ tmdbDrafts.total }} movies awaiting review
            </p>
          </div>

          <Link
            href="/featured-slots"
            class="text-sm text-red-500 hover:text-red-400"
          >
            Manage Featured Slots →
          </Link>
        </div>

        <!-- Search -->
        <div class="mb-6">
          <input
            v-model="search"
            type="text"
            placeholder="Search TMDB imports..."
            class="w-full rounded-lg border-zinc-700 bg-zinc-900 px-4 py-2 text-white placeholder-zinc-500 focus:border-red-500 focus:ring-red-500"
          />
        </div>

        <!-- TMDB Movies Grid -->
        <div v-if="tmdbDrafts.data.length > 0" class="mb-6">
          <div class="grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
            <div
              v-for="movie in tmdbDrafts.data"
              :key="movie.id"
              class="group relative overflow-hidden rounded-lg bg-zinc-900"
            >
              <div class="aspect-[2/3]">
                <img
                  :src="movie.poster_url || '/images/placeholders/poster-placeholder.png'"
                  :alt="movie.title"
                  class="h-full w-full object-cover"
                />
              </div>

              <div class="p-3">
                <h3 class="mb-1 truncate text-sm font-semibold text-white">
                  {{ movie.title }}
                </h3>
                <p class="mb-3 text-xs text-zinc-400">
                  {{ movie.release_year }}
                </p>

                <div class="flex gap-2">
                  <button
                    @click="publishMovie(movie)"
                    class="flex flex-1 items-center justify-center gap-1 rounded bg-green-600 px-2 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-green-700"
                  >
                    <CheckCircle class="size-3" />
                    Publish
                  </button>

                  <button
                    @click="archiveMovie(movie)"
                    class="flex flex-1 items-center justify-center gap-1 rounded bg-zinc-700 px-2 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-zinc-600"
                  >
                    <Archive class="size-3" />
                    Archive
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="tmdbDrafts.last_page > 1" class="mt-6 flex justify-center gap-2">
            <Link
              v-for="link in tmdbDrafts.links"
              :key="link.label"
              :href="link.url || '#'"
              :class="[
                'rounded px-3 py-2 text-sm',
                link.active
                  ? 'bg-red-600 text-white'
                  : link.url
                  ? 'bg-zinc-800 text-zinc-300 hover:bg-zinc-700'
                  : 'bg-zinc-900 text-zinc-600 cursor-not-allowed',
              ]"
              v-html="link.label"
            />
          </div>
        </div>

        <div v-else class="rounded-lg border border-zinc-800 bg-zinc-900 py-16 text-center">
          <p class="text-zinc-400">No TMDB imports found</p>
          <p class="mt-2 text-sm text-zinc-500">
            Run <code class="rounded bg-zinc-800 px-2 py-1">php artisan tmdb:import</code> to import movies
          </p>
        </div>
      </div>

      <!-- Quick Actions for Regular Users -->
      <div v-if="!auth.user?.is_admin" class="grid gap-6 sm:grid-cols-2">
        <Link
          href="/movies"
          class="group block rounded-lg bg-zinc-900 p-6 transition hover:bg-zinc-800"
        >
          <h2 class="text-xl font-semibold text-white">Browse Movies</h2>
          <p class="mt-2 text-sm text-zinc-400">
            Explore our collection of war films
          </p>
          <div class="mt-4 text-sm font-medium text-red-500 group-hover:text-red-400">
            View All Movies →
          </div>
        </Link>

        <Link
          href="/watchlist"
          class="group block rounded-lg bg-zinc-900 p-6 transition hover:bg-zinc-800"
        >
          <h2 class="text-xl font-semibold text-white">My Watchlist</h2>
          <p class="mt-2 text-sm text-zinc-400">
            View your saved movies
          </p>
          <div class="mt-4 text-sm font-medium text-red-500 group-hover:text-red-400">
            View Watchlist →
          </div>
        </Link>
      </div>
    </div>
  </div>
</template>
