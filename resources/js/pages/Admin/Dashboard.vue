<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

interface Props {
  stats: {
    movies: number
    tags: number
    activeFeatures: number
  }
}

const props = defineProps<Props>()
const page = usePage()
const auth = page.props.auth as { user: any }
</script>

<template>
  <AppLayout>
    <Head title="Admin Dashboard" />

    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-white">Admin Dashboard</h1>
        <p class="mt-2 text-zinc-400">
          Manage your war movies collection and featured content.
        </p>
      </div>

      <!-- Stats Grid -->
      <div class="mb-8 grid gap-6 sm:grid-cols-3">
        <div class="rounded-lg bg-zinc-900 p-6">
          <div class="text-sm font-medium text-zinc-400">Total Movies</div>
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

      <!-- Quick Actions -->
      <div class="grid gap-6 sm:grid-cols-2">
        <Link
          :href="'/admin/movies'"
          class="group block rounded-lg bg-zinc-900 p-6 transition hover:bg-zinc-800"
        >
          <h2 class="text-xl font-semibold text-white">Manage Movies</h2>
          <p class="mt-2 text-sm text-zinc-400">
            Add, edit, or remove movies from your collection.
          </p>
          <div class="mt-4 text-sm font-medium text-red-500 group-hover:text-red-400">
            View All Movies →
          </div>
        </Link>

        <Link
          :href="'/admin/featured-slots'"
          class="group block rounded-lg bg-zinc-900 p-6 transition hover:bg-zinc-800"
        >
          <h2 class="text-xl font-semibold text-white">Featured Slots</h2>
          <p class="mt-2 text-sm text-zinc-400">
            Manage hero section and pick of the week features.
          </p>
          <div class="mt-4 text-sm font-medium text-red-500 group-hover:text-red-400">
            View Featured Slots →
          </div>
        </Link>
      </div>
    </div>
  </AppLayout>
</template>
