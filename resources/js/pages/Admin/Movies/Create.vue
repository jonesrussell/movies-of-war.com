<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { Tag } from '@/types/models'

interface Props {
  tags: Tag[]
}

const props = defineProps<Props>()
const page = usePage()
const auth = page.props.auth as { user: any }

const form = useForm({
  title: '',
  release_year: new Date().getFullYear(),
  release_date: '',
  synopsis: '',
  runtime: null as number | null,
  country: '',
  conflict: '',
  poster_url: '',
  trailer_url: '',
  imdb_id: '',
  is_upcoming: false,
  tags: [] as number[],
})

function submit() {
  form.post('/movies')
}
</script>

<template>
  <AppLayout>
    <Head title="Add New Movie - Admin" />

    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-white">Add New Movie</h1>
        <p class="mt-2 text-zinc-400">Add a new war movie to your collection.</p>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- Title -->
        <div>
          <label for="title" class="block text-sm font-medium text-zinc-300">
            Title <span class="text-red-500">*</span>
          </label>
          <input
            id="title"
            v-model="form.title"
            type="text"
            required
            class="mt-1 w-full rounded-lg border-zinc-700 bg-zinc-900 text-white focus:border-red-500 focus:ring-red-500"
          />
          <div v-if="form.errors.title" class="mt-1 text-sm text-red-500">
            {{ form.errors.title }}
          </div>
        </div>

        <!-- Release Year & Date -->
        <div class="grid gap-6 sm:grid-cols-2">
          <div>
            <label for="release_year" class="block text-sm font-medium text-zinc-300">
              Release Year <span class="text-red-500">*</span>
            </label>
            <input
              id="release_year"
              v-model.number="form.release_year"
              type="number"
              required
              min="1900"
              max="2100"
              class="mt-1 w-full rounded-lg border-zinc-700 bg-zinc-900 text-white focus:border-red-500 focus:ring-red-500"
            />
            <div v-if="form.errors.release_year" class="mt-1 text-sm text-red-500">
              {{ form.errors.release_year }}
            </div>
          </div>

          <div>
            <label for="release_date" class="block text-sm font-medium text-zinc-300">
              Release Date
            </label>
            <input
              id="release_date"
              v-model="form.release_date"
              type="date"
              class="mt-1 w-full rounded-lg border-zinc-700 bg-zinc-900 text-white focus:border-red-500 focus:ring-red-500"
            />
            <div v-if="form.errors.release_date" class="mt-1 text-sm text-red-500">
              {{ form.errors.release_date }}
            </div>
          </div>
        </div>

        <!-- Synopsis -->
        <div>
          <label for="synopsis" class="block text-sm font-medium text-zinc-300">
            Synopsis <span class="text-red-500">*</span>
          </label>
          <textarea
            id="synopsis"
            v-model="form.synopsis"
            required
            rows="4"
            class="mt-1 w-full rounded-lg border-zinc-700 bg-zinc-900 text-white focus:border-red-500 focus:ring-red-500"
          />
          <div v-if="form.errors.synopsis" class="mt-1 text-sm text-red-500">
            {{ form.errors.synopsis }}
          </div>
        </div>

        <!-- Country, Conflict, Runtime -->
        <div class="grid gap-6 sm:grid-cols-3">
          <div>
            <label for="country" class="block text-sm font-medium text-zinc-300">
              Country
            </label>
            <input
              id="country"
              v-model="form.country"
              type="text"
              class="mt-1 w-full rounded-lg border-zinc-700 bg-zinc-900 text-white focus:border-red-500 focus:ring-red-500"
            />
            <div v-if="form.errors.country" class="mt-1 text-sm text-red-500">
              {{ form.errors.country }}
            </div>
          </div>

          <div>
            <label for="conflict" class="block text-sm font-medium text-zinc-300">
              Conflict
            </label>
            <input
              id="conflict"
              v-model="form.conflict"
              type="text"
              class="mt-1 w-full rounded-lg border-zinc-700 bg-zinc-900 text-white focus:border-red-500 focus:ring-red-500"
            />
            <div v-if="form.errors.conflict" class="mt-1 text-sm text-red-500">
              {{ form.errors.conflict }}
            </div>
          </div>

          <div>
            <label for="runtime" class="block text-sm font-medium text-zinc-300">
              Runtime (min)
            </label>
            <input
              id="runtime"
              v-model.number="form.runtime"
              type="number"
              min="1"
              class="mt-1 w-full rounded-lg border-zinc-700 bg-zinc-900 text-white focus:border-red-500 focus:ring-red-500"
            />
            <div v-if="form.errors.runtime" class="mt-1 text-sm text-red-500">
              {{ form.errors.runtime }}
            </div>
          </div>
        </div>

        <!-- Poster URL & Trailer URL -->
        <div class="grid gap-6 sm:grid-cols-2">
          <div>
            <label for="poster_url" class="block text-sm font-medium text-zinc-300">
              Poster URL
            </label>
            <input
              id="poster_url"
              v-model="form.poster_url"
              type="url"
              class="mt-1 w-full rounded-lg border-zinc-700 bg-zinc-900 text-white focus:border-red-500 focus:ring-red-500"
            />
            <div v-if="form.errors.poster_url" class="mt-1 text-sm text-red-500">
              {{ form.errors.poster_url }}
            </div>
          </div>

          <div>
            <label for="trailer_url" class="block text-sm font-medium text-zinc-300">
              Trailer URL
            </label>
            <input
              id="trailer_url"
              v-model="form.trailer_url"
              type="url"
              class="mt-1 w-full rounded-lg border-zinc-700 bg-zinc-900 text-white focus:border-red-500 focus:ring-red-500"
            />
            <div v-if="form.errors.trailer_url" class="mt-1 text-sm text-red-500">
              {{ form.errors.trailer_url }}
            </div>
          </div>
        </div>

        <!-- IMDb ID -->
        <div>
          <label for="imdb_id" class="block text-sm font-medium text-zinc-300">
            IMDb ID
          </label>
          <input
            id="imdb_id"
            v-model="form.imdb_id"
            type="text"
            placeholder="tt0120815"
            class="mt-1 w-full rounded-lg border-zinc-700 bg-zinc-900 text-white focus:border-red-500 focus:ring-red-500"
          />
          <div v-if="form.errors.imdb_id" class="mt-1 text-sm text-red-500">
            {{ form.errors.imdb_id }}
          </div>
        </div>

        <!-- Tags -->
        <div>
          <label class="block text-sm font-medium text-zinc-300">Tags</label>
          <div class="mt-2 grid gap-3 sm:grid-cols-3">
            <label
              v-for="tag in tags"
              :key="tag.id"
              class="flex items-center gap-2 rounded-lg bg-zinc-900 p-3 cursor-pointer hover:bg-zinc-800"
            >
              <input
                v-model="form.tags"
                type="checkbox"
                :value="tag.id"
                class="rounded border-zinc-700 bg-zinc-800 text-red-600 focus:ring-red-500"
              />
              <span class="text-sm text-zinc-300">{{ tag.name }}</span>
            </label>
          </div>
          <div v-if="form.errors.tags" class="mt-1 text-sm text-red-500">
            {{ form.errors.tags }}
          </div>
        </div>

        <!-- Is Upcoming -->
        <div>
          <label class="flex items-center gap-2">
            <input
              v-model="form.is_upcoming"
              type="checkbox"
              class="rounded border-zinc-700 bg-zinc-800 text-red-600 focus:ring-red-500"
            />
            <span class="text-sm text-zinc-300">Mark as upcoming release</span>
          </label>
          <div v-if="form.errors.is_upcoming" class="mt-1 text-sm text-red-500">
            {{ form.errors.is_upcoming }}
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-4">
          <a
            href="/dashboard/movies"
            class="rounded-lg bg-zinc-800 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700"
          >
            Cancel
          </a>
          <button
            type="submit"
            :disabled="form.processing"
            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50"
          >
            {{ form.processing ? 'Creating...' : 'Create Movie' }}
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
