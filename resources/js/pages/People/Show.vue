<script setup lang="ts">
import type { Person } from '@/types';

import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, MapPin } from 'lucide-vue-next';

import { Poster } from '@/components/primitives';
import PublicContainer from '@/components/public/PublicContainer.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { getProfileImageUrl } from '@/utils/image';

interface Props {
    person: Person;
}

defineProps<Props>();
</script>

<template>
    <PublicLayout>
        <Head :title="`${person.name} - Movies of War`">
            <meta
                name="description"
                :content="
                    person.biography
                        ? person.biography.slice(0, 200) + '...'
                        : `Filmography and biographical information for ${person.name}`
                "
            />
        </Head>

        <div class="min-h-screen bg-zinc-950">
            <PublicContainer class="py-12 sm:py-14 lg:py-16">
                <Link
                    href="/movies"
                    class="group mb-6 inline-flex items-center gap-2 text-sm text-zinc-300 transition-colors hover:text-white focus:outline-none focus-visible:text-white"
                >
                    <ArrowLeft
                        class="size-4 transition-transform duration-200 group-hover:-translate-x-1"
                    />
                    Back to Movies
                </Link>

                <div class="flex flex-col gap-8 md:flex-row md:gap-12">
                    <!-- Profile photo -->
                    <div class="shrink-0">
                        <img
                            :src="
                                getProfileImageUrl(person.profile_path, 'w500')
                            "
                            :alt="person.name"
                            class="w-48 rounded-2xl object-cover shadow-xl ring-1 ring-zinc-800/70 md:w-64"
                        />
                    </div>

                    <div class="min-w-0 flex-1">
                        <h1
                            class="mb-4 text-3xl font-semibold tracking-tight text-white md:text-4xl"
                        >
                            {{ person.name }}
                        </h1>

                        <!-- Facts -->
                        <dl
                            v-if="
                                person.birthday ||
                                person.deathday ||
                                person.place_of_birth ||
                                (person.also_known_as &&
                                    person.also_known_as.length > 0)
                            "
                            class="mb-6 grid gap-2"
                        >
                            <template v-if="person.birthday">
                                <dt class="sr-only">Birthday</dt>
                                <dd
                                    class="flex items-center gap-2 text-sm text-zinc-300"
                                >
                                    <Calendar class="size-4 shrink-0" />
                                    {{ person.birthday }}
                                    <span v-if="person.deathday">
                                        – {{ person.deathday }}
                                    </span>
                                </dd>
                            </template>
                            <template v-if="person.place_of_birth">
                                <dt class="sr-only">Place of birth</dt>
                                <dd
                                    class="flex items-center gap-2 text-sm text-zinc-300"
                                >
                                    <MapPin class="size-4 shrink-0" />
                                    {{ person.place_of_birth }}
                                </dd>
                            </template>
                            <template
                                v-if="
                                    person.also_known_as &&
                                    person.also_known_as.length > 0
                                "
                            >
                                <dt class="sr-only">Also known as</dt>
                                <dd class="text-sm text-zinc-300">
                                    {{ person.also_known_as.join(', ') }}
                                </dd>
                            </template>
                        </dl>

                        <!-- Biography -->
                        <div v-if="person.biography" class="mb-8">
                            <h2 class="mb-3 text-xl font-semibold text-white">
                                Biography
                            </h2>
                            <p
                                class="leading-relaxed whitespace-pre-line text-zinc-300"
                            >
                                {{ person.biography }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Known For -->
                <section
                    v-if="person.known_for && person.known_for.length > 0"
                    class="mt-12"
                >
                    <h2 class="mb-6 text-xl font-semibold text-white">
                        Known For
                    </h2>
                    <div
                        class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5"
                    >
                        <Link
                            v-for="entry in person.known_for"
                            :key="`${entry.movie_id}-${entry.character ?? entry.job ?? ''}`"
                            :href="`/movies/${entry.movie_slug}`"
                            class="group flex flex-col gap-2"
                        >
                            <div
                                class="overflow-hidden rounded-xl ring-1 ring-zinc-800/70 transition-all hover:ring-zinc-700"
                            >
                                <Poster
                                    :src="entry.poster_url"
                                    :alt="entry.movie_title"
                                    :poster-path="null"
                                    context="grid"
                                    aspect-ratio="2/3"
                                    class="transition-transform duration-300 group-hover:scale-105"
                                />
                            </div>
                            <div class="min-w-0">
                                <span
                                    class="block truncate text-sm font-medium text-white group-hover:text-red-400"
                                >
                                    {{ entry.movie_title }}
                                </span>
                                <span
                                    v-if="entry.character"
                                    class="block truncate text-xs text-zinc-400"
                                >
                                    {{ entry.character }}
                                </span>
                                <span
                                    v-else-if="entry.job"
                                    class="block truncate text-xs text-zinc-400"
                                >
                                    {{ entry.job }}
                                </span>
                                <span
                                    v-else-if="entry.release_year"
                                    class="block text-xs text-zinc-400"
                                >
                                    {{ entry.release_year }}
                                </span>
                            </div>
                        </Link>
                    </div>
                </section>

                <!-- Filmography: Cast -->
                <section
                    v-if="
                        person.filmography_cast &&
                        person.filmography_cast.length > 0
                    "
                    class="mt-12"
                >
                    <h2 class="mb-4 text-xl font-semibold text-white">
                        Acting
                    </h2>
                    <ul
                        class="divide-y divide-zinc-800/70 rounded-xl ring-1 ring-zinc-800/70"
                    >
                        <li
                            v-for="entry in person.filmography_cast"
                            :key="`cast-${entry.movie_id}-${entry.character ?? ''}`"
                            class="flex items-center justify-between gap-4 px-4 py-3 transition-colors hover:bg-zinc-900/40"
                        >
                            <Link
                                :href="`/movies/${entry.movie_slug}`"
                                class="min-w-0 flex-1 font-medium text-white hover:text-red-400"
                            >
                                {{ entry.movie_title }}
                            </Link>
                            <span
                                v-if="entry.character"
                                class="shrink-0 text-sm text-zinc-400"
                            >
                                {{ entry.character }}
                            </span>
                            <span
                                v-if="entry.release_year"
                                class="shrink-0 text-sm text-zinc-500"
                            >
                                {{ entry.release_year }}
                            </span>
                        </li>
                    </ul>
                </section>

                <!-- Filmography: Crew -->
                <section
                    v-if="
                        person.filmography_crew &&
                        person.filmography_crew.length > 0
                    "
                    class="mt-12"
                >
                    <h2 class="mb-4 text-xl font-semibold text-white">Crew</h2>
                    <ul
                        class="divide-y divide-zinc-800/70 rounded-xl ring-1 ring-zinc-800/70"
                    >
                        <li
                            v-for="entry in person.filmography_crew"
                            :key="`crew-${entry.movie_id}-${entry.job ?? ''}`"
                            class="flex items-center justify-between gap-4 px-4 py-3 transition-colors hover:bg-zinc-900/40"
                        >
                            <Link
                                :href="`/movies/${entry.movie_slug}`"
                                class="min-w-0 flex-1 font-medium text-white hover:text-red-400"
                            >
                                {{ entry.movie_title }}
                            </Link>
                            <span
                                v-if="entry.job"
                                class="shrink-0 text-sm text-zinc-400"
                            >
                                {{ entry.job }}
                            </span>
                            <span
                                v-if="entry.release_year"
                                class="shrink-0 text-sm text-zinc-500"
                            >
                                {{ entry.release_year }}
                            </span>
                        </li>
                    </ul>
                </section>

                <div
                    v-if="
                        (!person.known_for || person.known_for.length === 0) &&
                        (!person.filmography_cast ||
                            person.filmography_cast.length === 0) &&
                        (!person.filmography_crew ||
                            person.filmography_crew.length === 0)
                    "
                    class="mt-12 rounded-xl bg-zinc-900/40 p-8 text-center text-zinc-400"
                >
                    No filmography available yet.
                </div>
            </PublicContainer>
        </div>
    </PublicLayout>
</template>
