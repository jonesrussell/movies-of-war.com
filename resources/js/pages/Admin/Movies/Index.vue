<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { Movie, PaginatedMovies } from '@/types/models'

interface Props {
  movies: PaginatedMovies
  queryParams: {
    search?: string
  }
}

const props = defineProps<Props>()
const page = usePage()
const auth = page.props.auth as { user: any }

const search = ref(props.queryParams.search || '')

watch(search, (value) => {
  router.get('/admin/movies', {
    search: value || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}, { debounce: 300 })

function deleteMovie(movie: Movie) {
  if (confirm(`Are you sure you want to delete "${movie.title}"?`)) {
    router.delete(`/admin/movies/${movie.id}`)
  }
}
</script>

<template>
  <AppLayout>
    <Head title="Manage Movies - Admin" />

    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8 flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-white">Manage Movies</h1>
          <p class="mt-2 text-zinc-400">{{ movies.total }} total movies</p>
        </div>
        <Link
          :href="'/admin/movies/create'"
          class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
        >
          Add New Movie
        </Link>
      </div>

      <!-- Search -->
      <div class="mb-6">
        <input
          v-model="search"
          type="text"
          placeholder="Search movies..."
          class="w-full rounded-lg border-zinc-700 bg-zinc-900 px-4 py-2 text-white placeholder-zinc-500 focus:border-red-500 focus:ring-red-500"
        />
      </div>

      <!-- Movies Table -->
      <div class="overflow-hidden rounded-lg bg-zinc-900">
        <table class="min-w-full divide-y divide-zinc-800">
          <thead class="bg-zinc-950">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-400">
                Title
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-400">
                Year
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-400">
                Tags
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-400">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-zinc-800">
            <tr v-for="movie in movies.data" :key="movie.id" class="hover:bg-zinc-800/50">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <img
                    v-if="movie.poster_url"
                    :src="movie.poster_url"
                    :alt="movie.title"
                    class="h-12 w-8 rounded object-cover"
                  />
                  <div class="h-12 w-8 rounded bg-zinc-800" v-else />
                  <div>
                    <div class="font-medium text-white">{{ movie.title }}</div>
                    <div class="text-sm text-zinc-400">{{ movie.country }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 text-sm text-zinc-300">
                {{ movie.release_year }}
              </td>
              <td class="px-6 py-4">
                <div class="flex flex-wrap gap-1">
                  <span
                    v-for="tag in movie.tags?.slice(0, 3)"
                    :key="tag.id"
                    class="rounded bg-zinc-800 px-2 py-1 text-xs text-zinc-300"
                  >
                    {{ tag.name }}
                  </span>
                  <span
                    v-if="movie.tags && movie.tags.length > 3"
                    class="rounded bg-zinc-800 px-2 py-1 text-xs text-zinc-400"
                  >
                    +{{ movie.tags.length - 3 }}
                  </span>
                </div>
              </td>
              <td class="px-6 py-4 text-right text-sm">
                <Link
                  :href="`/admin/movies/${movie.id}/edit`"
                  class="text-red-500 hover:text-red-400 mr-4"
                >
                  Edit
                </Link>
                <button
                  @click="deleteMovie(movie)"
                  class="text-zinc-400 hover:text-white"
                >
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="movies.last_page > 1" class="mt-6 flex justify-center gap-2">
        <Link
          v-for="page in movies.links"
          :key="page.label"
          :href="page.url || '#'"
          :class="[
            'rounded px-3 py-2 text-sm',
            page.active
              ? 'bg-red-600 text-white'
              : page.url
              ? 'bg-zinc-800 text-zinc-300 hover:bg-zinc-700'
              : 'bg-zinc-900 text-zinc-600 cursor-not-allowed',
          ]"
          v-html="page.label"
        />
      </div>
    </div>
  </AppLayout>
</template>
