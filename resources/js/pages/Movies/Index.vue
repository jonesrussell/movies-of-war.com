<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { Search, Filter, X } from 'lucide-vue-next'
import type { Movie, Tag, PaginatedMovies, MovieFilters } from '@/types'
import MovieCard from '@/components/MovieCard.vue'

interface Props {
  movies: PaginatedMovies
  filters: {
    countries: string[]
    conflicts: string[]
    years: number[]
    tags: Tag[]
  }
  queryParams: MovieFilters
}

const props = defineProps<Props>()
const page = usePage()
const auth = page.props.auth as { user: any }

const search = ref(props.queryParams.search || '')
const selectedYear = ref(props.queryParams.year || '')
const selectedCountry = ref(props.queryParams.country || '')
const selectedConflict = ref(props.queryParams.conflict || '')
const selectedTag = ref(props.queryParams.tag || '')
const showFilters = ref(false)

watch([search, selectedYear, selectedCountry, selectedConflict, selectedTag], () => {
  router.get('/movies', {
    search: search.value || undefined,
    year: selectedYear.value || undefined,
    country: selectedCountry.value || undefined,
    conflict: selectedConflict.value || undefined,
    tag: selectedTag.value || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}, { debounce: 300 })

const clearFilters = () => {
  search.value = ''
  selectedYear.value = ''
  selectedCountry.value = ''
  selectedConflict.value = ''
  selectedTag.value = ''
}

const hasActiveFilters = () => {
  return search.value || selectedYear.value || selectedCountry.value || selectedConflict.value || selectedTag.value
}
</script>

<template>
  <Head title="Browse Movies - Movies of War" />

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
              class="text-red-500"
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
            </template>
          </nav>
        </div>
      </div>
    </header>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <div class="mb-8">
        <h1 class="mb-4 text-4xl font-bold text-white">Browse War Films</h1>
        <p class="text-zinc-400">
          {{ movies.meta?.total ?? 0 }} films spanning multiple conflicts and eras
        </p>
      </div>

      <div class="mb-8 space-y-4">
        <div class="flex flex-wrap items-center gap-4">
          <div class="relative flex-1 min-w-[300px]">
            <Search class="absolute left-3 top-1/2 size-5 -translate-y-1/2 text-zinc-500" />
            <input
              v-model="search"
              type="text"
              placeholder="Search movies..."
              class="w-full rounded-lg border border-zinc-800 bg-zinc-900 py-2.5 pl-10 pr-4 text-white placeholder-zinc-500 focus:border-red-600 focus:outline-none focus:ring-1 focus:ring-red-600"
            />
          </div>

          <button
            @click="showFilters = !showFilters"
            class="flex items-center gap-2 rounded-lg border border-zinc-800 bg-zinc-900 px-4 py-2.5 text-white transition-colors hover:border-zinc-700"
          >
            <Filter class="size-5" />
            Filters
            <span v-if="hasActiveFilters()" class="rounded-full bg-red-600 px-2 py-0.5 text-xs">
              {{ [search, selectedYear, selectedCountry, selectedConflict, selectedTag].filter(Boolean).length }}
            </span>
          </button>

          <button
            v-if="hasActiveFilters()"
            @click="clearFilters"
            class="flex items-center gap-2 text-zinc-400 transition-colors hover:text-white"
          >
            <X class="size-5" />
            Clear
          </button>
        </div>

        <div v-if="showFilters" class="grid gap-4 rounded-lg border border-zinc-800 bg-zinc-900 p-4 sm:grid-cols-2 lg:grid-cols-4">
          <div>
            <label class="mb-2 block text-sm font-medium text-zinc-300">Year</label>
            <select
              v-model="selectedYear"
              class="w-full rounded-lg border border-zinc-800 bg-zinc-950 px-3 py-2 text-white focus:border-red-600 focus:outline-none focus:ring-1 focus:ring-red-600"
            >
              <option value="">All Years</option>
              <option v-for="year in filters.years" :key="year" :value="year">
                {{ year }}
              </option>
            </select>
          </div>

          <div>
            <label class="mb-2 block text-sm font-medium text-zinc-300">Country</label>
            <select
              v-model="selectedCountry"
              class="w-full rounded-lg border border-zinc-800 bg-zinc-950 px-3 py-2 text-white focus:border-red-600 focus:outline-none focus:ring-1 focus:ring-red-600"
            >
              <option value="">All Countries</option>
              <option v-for="country in filters.countries" :key="country" :value="country">
                {{ country }}
              </option>
            </select>
          </div>

          <div>
            <label class="mb-2 block text-sm font-medium text-zinc-300">Conflict</label>
            <select
              v-model="selectedConflict"
              class="w-full rounded-lg border border-zinc-800 bg-zinc-950 px-3 py-2 text-white focus:border-red-600 focus:outline-none focus:ring-1 focus:ring-red-600"
            >
              <option value="">All Conflicts</option>
              <option v-for="conflict in filters.conflicts" :key="conflict" :value="conflict">
                {{ conflict }}
              </option>
            </select>
          </div>

          <div>
            <label class="mb-2 block text-sm font-medium text-zinc-300">Tag</label>
            <select
              v-model="selectedTag"
              class="w-full rounded-lg border border-zinc-800 bg-zinc-950 px-3 py-2 text-white focus:border-red-600 focus:outline-none focus:ring-1 focus:ring-red-600"
            >
              <option value="">All Tags</option>
              <option v-for="tag in filters.tags" :key="tag.id" :value="tag.slug">
                {{ tag.name }}
              </option>
            </select>
          </div>
        </div>
      </div>

      <div v-if="movies.data && movies.data.length > 0">
        <div class="mb-8 grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
          <MovieCard v-for="movie in movies.data" :key="movie.id" :movie="movie" />
        </div>

        <div v-if="movies.meta && movies.meta.last_page > 1" class="flex items-center justify-center gap-2">
          <Link
            v-for="link in movies.meta.links"
            :key="link.label"
            :href="link.url || '#'"
            :class="[
              'px-4 py-2 rounded-lg transition-colors',
              link.active
                ? 'bg-red-600 text-white'
                : link.url
                ? 'bg-zinc-900 text-zinc-300 hover:bg-zinc-800'
                : 'bg-zinc-900 text-zinc-600 cursor-not-allowed'
            ]"
            :preserve-scroll="true"
            :preserve-state="true"
            v-html="link.label"
          />
        </div>
      </div>

      <div v-else class="py-16 text-center">
        <img
          src="/images/illustrations/no-movies-found.png"
          alt="No movies found"
          class="mx-auto mb-6 h-32 w-32 opacity-50"
        />
        <p class="text-xl text-zinc-400">No movies found matching your filters.</p>
        <button
          @click="clearFilters"
          class="mt-4 text-red-500 hover:text-red-400"
        >
          Clear filters
        </button>
      </div>
    </div>
  </div>
</template>
