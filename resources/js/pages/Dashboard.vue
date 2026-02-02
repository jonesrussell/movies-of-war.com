<script setup lang="ts">
import type { AppPageProps, Movie, Review } from '@/types';

import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Archive,
    Bookmark,
    Database,
    FileEdit,
    Film,
    Plus,
    Star,
    Tags,
} from 'lucide-vue-next';
import { ArrowRight } from 'lucide-vue-next';
import { computed } from 'vue';

import AdminQuickLinks from '@/components/dashboard/AdminQuickLinks.vue';
import DashboardStatCard from '@/components/dashboard/DashboardStatCard.vue';
import RecentMoviesCard from '@/components/dashboard/RecentMoviesCard.vue';
import { StarRating } from '@/components/primitives';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';

interface XStats {
    published_posts: number;
    scheduled_posts: number;
    failed_posts: number;
    total_impressions: number;
    active_keywords: number;
    engagement_rate: number;
}

interface AdminStats {
    movies: number;
    drafts: number;
    archived: number;
    tags: number;
    activeFeatures: number;
    tmdbDrafts: number;
    x_published_posts?: number;
    x_scheduled_posts?: number;
    x_failed_posts?: number;
    x_total_impressions?: number;
    x_active_keywords?: number;
}

interface UserStats {
    movies_total: number;
    watchlist_count: number;
    reviews_count: number;
}

interface Props {
    stats: AdminStats | UserStats;
    recentMovies: Movie[];
    watchlistCount?: number;
    xStats?: XStats | null;
    recentReviews?: Review[];
}

interface PageProps extends AppPageProps {
    stats: Props['stats'];
    recentMovies: Movie[];
    watchlistCount?: number;
    xStats?: XStats | null;
    recentReviews?: Review[];
}

defineProps<Props>();
const page = usePage<PageProps>();

const auth = page.props.auth;
const isAdmin = computed(() => auth.user?.is_admin);

const userStats = computed(() => {
    const s = page.props.stats;
    return 'reviews_count' in s ? (s as UserStats) : null;
});

const adminStats = computed(() => {
    const s = page.props.stats;
    return 'reviews_count' in s ? null : (s as AdminStats);
});

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Good morning';
    if (hour < 17) return 'Good afternoon';
    return 'Good evening';
});

const firstName = computed(() => {
    return auth.user?.name?.split(' ')[0] || 'there';
});
</script>

<template>
    <Head title="Dashboard - Movies of War" />

    <AppSidebarLayout>
        <div
            class="dashboard-container w-full"
            style="container-type: inline-size"
        >
            <div class="px-4 py-8 sm:px-6 lg:px-8">
                <!-- Welcome Header -->
                <div class="mb-8">
                    <h1
                        class="text-2xl font-bold tracking-tight text-white sm:text-3xl"
                    >
                        {{ greeting }}, {{ firstName }}
                    </h1>
                    <p class="mt-1 text-zinc-300">
                        <template v-if="isAdmin">
                            Here's what's happening with your war films
                            collection.
                        </template>
                        <template v-else>
                            Welcome back to Movies of War.
                        </template>
                    </p>
                </div>

                <!-- Admin Stats Grid -->
                <div
                    v-if="isAdmin && adminStats"
                    class="dashboard-stats-grid mb-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6"
                >
                    <DashboardStatCard
                        title="Published"
                        :value="adminStats.movies"
                        :icon="Film"
                        href="/movies"
                        :trend="null"
                        variant="success"
                    />
                    <DashboardStatCard
                        title="Drafts"
                        :value="adminStats.drafts"
                        :icon="FileEdit"
                        href="/dashboard/tmdb/imports"
                        :trend="null"
                        variant="warning"
                    />
                    <DashboardStatCard
                        title="Archived"
                        :value="adminStats.archived"
                        :icon="Archive"
                        :href="null"
                        :trend="null"
                        variant="default"
                    />
                    <DashboardStatCard
                        title="Tags"
                        :value="adminStats.tags"
                        :icon="Tags"
                        :href="null"
                        :trend="null"
                        variant="default"
                    />
                    <DashboardStatCard
                        title="Featured"
                        :value="adminStats.activeFeatures"
                        :icon="Star"
                        href="/featured-slots"
                        :trend="null"
                        variant="default"
                    />
                    <DashboardStatCard
                        title="TMDB Queue"
                        :value="adminStats.tmdbDrafts"
                        :icon="Database"
                        href="/dashboard/tmdb/imports"
                        :trend="null"
                        :variant="adminStats.tmdbDrafts > 0 ? 'warning' : 'default'"
                    />
                </div>

                <!-- Regular User Stats -->
                <div v-else class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-3">
                    <DashboardStatCard
                        title="My Reviews"
                        :value="userStats?.reviews_count ?? 0"
                        :icon="Star"
                        href="/my-reviews"
                        :trend="null"
                        :variant="
                            (userStats?.reviews_count ?? 0) > 0
                                ? 'success'
                                : 'default'
                        "
                    />
                    <DashboardStatCard
                        title="My Watchlist"
                        :value="userStats?.watchlist_count ?? 0"
                        :icon="Bookmark"
                        href="/watchlist"
                        :trend="null"
                        variant="success"
                    />
                    <DashboardStatCard
                        title="Movies Available"
                        :value="userStats?.movies_total ?? 0"
                        :icon="Film"
                        href="/movies"
                        :trend="null"
                        variant="default"
                    />
                </div>

                <!-- Main Content Grid -->
                <div class="dashboard-main-grid grid gap-6 lg:grid-cols-2">
                    <!-- Recent Movies -->
                    <div class="lg:col-span-2">
                        <RecentMoviesCard
                            :movies="recentMovies"
                            :title="
                                isAdmin ? 'Recently Updated' : 'Recently Added'
                            "
                        />
                    </div>

                    <!-- Non-Admin: My Recent Reviews -->
                    <div v-if="!isAdmin" class="lg:col-span-2">
                        <div
                            class="rounded-xl bg-zinc-900 p-5 ring-1 ring-zinc-800/70"
                        >
                            <div class="flex items-center justify-between">
                                <h3 class="text-base font-semibold text-white">
                                    My Recent Reviews
                                </h3>
                                <Link
                                    href="/my-reviews"
                                    class="flex items-center gap-1 text-sm font-medium text-red-500 transition-colors hover:text-red-400"
                                >
                                    View all
                                    <ArrowRight class="size-3.5" />
                                </Link>
                            </div>

                            <div
                                v-if="
                                    page.props.recentReviews &&
                                    page.props.recentReviews.length > 0
                                "
                                class="mt-4 flex flex-col gap-3"
                            >
                                <Link
                                    v-for="review in page.props.recentReviews"
                                    :key="review.id"
                                    :href="`/reviews/${review.id}`"
                                    class="flex items-center justify-between gap-4 rounded-lg bg-zinc-800/50 p-3 ring-1 ring-zinc-700/50 transition-colors hover:bg-zinc-800/70 hover:ring-zinc-600/50"
                                >
                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="truncate font-medium text-white"
                                        >
                                            {{ review.movie?.title }}
                                        </p>
                                        <p class="text-sm text-zinc-400">
                                            {{ review.formatted_date }}
                                        </p>
                                    </div>
                                    <div class="shrink-0">
                                        <StarRating
                                            :rating="review.rating"
                                            :max-stars="4"
                                            size="sm"
                                        />
                                    </div>
                                </Link>
                            </div>

                            <div
                                v-else
                                class="mt-4 flex flex-col items-center justify-center gap-4 rounded-lg bg-zinc-800/50 py-10"
                            >
                                <p class="text-center text-sm text-zinc-500">
                                    You haven't written any reviews yet. Browse
                                    movies to get started.
                                </p>
                                <Link
                                    href="/movies"
                                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-red-500"
                                >
                                    Browse movies
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Admin: TMDB Import Card -->
                    <div v-if="isAdmin && adminStats">
                        <Link
                            href="/dashboard/tmdb/imports"
                            class="group block rounded-xl bg-zinc-900 p-5 ring-1 ring-zinc-800/70 transition-all hover:bg-zinc-800/70 hover:ring-zinc-700/70"
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex size-12 items-center justify-center rounded-xl bg-amber-500/10"
                                >
                                    <Database class="size-6 text-amber-400" />
                                </div>
                                <div class="flex-1">
                                    <h3
                                        class="text-base font-semibold text-white"
                                    >
                                        TMDB Imports
                                    </h3>
                                    <p class="text-sm text-zinc-400">
                                        {{ adminStats.tmdbDrafts }}
                                        {{
                                            adminStats.tmdbDrafts === 1
                                                ? 'movie'
                                                : 'movies'
                                        }}
                                        awaiting review
                                    </p>
                                </div>
                                <div
                                    class="flex size-9 items-center justify-center rounded-lg bg-zinc-800 transition-colors group-hover:bg-zinc-700"
                                >
                                    <Plus class="size-4 text-zinc-400" />
                                </div>
                            </div>
                        </Link>
                    </div>

                    <!-- Admin: Search TMDB -->
                    <div v-if="isAdmin">
                        <Link
                            href="/dashboard/tmdb/search"
                            class="group block rounded-xl bg-zinc-900 p-5 ring-1 ring-zinc-800/70 transition-all hover:bg-zinc-800/70 hover:ring-zinc-700/70"
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex size-12 items-center justify-center rounded-xl bg-red-500/10"
                                >
                                    <svg
                                        class="size-6 text-red-400"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <circle cx="11" cy="11" r="8" />
                                        <path d="m21 21-4.3-4.3" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h3
                                        class="text-base font-semibold text-white"
                                    >
                                        Search TMDB
                                    </h3>
                                    <p class="text-sm text-zinc-400">
                                        Find and import specific movies
                                    </p>
                                </div>
                            </div>
                        </Link>
                    </div>

                    <!-- Admin: X Platform Section -->
                    <div v-if="isAdmin" class="lg:col-span-2">
                        <AdminQuickLinks :x-stats="xStats ?? null" />
                    </div>

                    <!-- Regular User: Quick Actions -->
                    <template v-if="!isAdmin">
                        <Link
                            href="/movies"
                            class="group block rounded-xl bg-zinc-900 p-5 ring-1 ring-zinc-800/70 transition-all hover:bg-zinc-800/70 hover:ring-zinc-700/70"
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex size-12 items-center justify-center rounded-xl bg-red-500/10"
                                >
                                    <Film class="size-6 text-red-400" />
                                </div>
                                <div class="flex-1">
                                    <h3
                                        class="text-base font-semibold text-white"
                                    >
                                        Browse Movies
                                    </h3>
                                    <p class="text-sm text-zinc-400">
                                        Explore the collection
                                    </p>
                                </div>
                            </div>
                        </Link>

                        <Link
                            href="/watchlist"
                            class="group block rounded-xl bg-zinc-900 p-5 ring-1 ring-zinc-800/70 transition-all hover:bg-zinc-800/70 hover:ring-zinc-700/70"
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex size-12 items-center justify-center rounded-xl bg-emerald-500/10"
                                >
                                    <Bookmark class="size-6 text-emerald-400" />
                                </div>
                                <div class="flex-1">
                                    <h3
                                        class="text-base font-semibold text-white"
                                    >
                                        My Watchlist
                                    </h3>
                                    <p class="text-sm text-zinc-400">
                                        {{ userStats?.watchlist_count ?? 0 }}
                                        {{
                                            (userStats?.watchlist_count ??
                                                0) === 1
                                                ? 'movie'
                                                : 'movies'
                                        }}
                                        saved
                                    </p>
                                </div>
                            </div>
                        </Link>

                        <Link
                            href="/my-reviews"
                            class="group block rounded-xl bg-zinc-900 p-5 ring-1 ring-zinc-800/70 transition-all hover:bg-zinc-800/70 hover:ring-zinc-700/70"
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex size-12 items-center justify-center rounded-xl bg-amber-500/10"
                                >
                                    <Star class="size-6 text-amber-400" />
                                </div>
                                <div class="flex-1">
                                    <h3
                                        class="text-base font-semibold text-white"
                                    >
                                        My Reviews
                                    </h3>
                                    <p class="text-sm text-zinc-400">
                                        {{ userStats?.reviews_count ?? 0 }}
                                        {{
                                            (userStats?.reviews_count ?? 0) ===
                                            1
                                                ? 'review'
                                                : 'reviews'
                                        }}
                                    </p>
                                </div>
                            </div>
                        </Link>
                    </template>
                </div>

                <!-- Admin: Featured Slots Management -->
                <div v-if="isAdmin && adminStats" class="mt-6">
                    <Link
                        href="/featured-slots"
                        class="group block rounded-xl bg-gradient-to-r from-red-600/20 via-red-500/10 to-transparent p-5 ring-1 ring-red-500/20 transition-all hover:ring-red-500/40"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex size-12 items-center justify-center rounded-xl bg-red-500/20"
                                >
                                    <Star class="size-6 text-red-400" />
                                </div>
                                <div>
                                    <h3
                                        class="text-base font-semibold text-white"
                                    >
                                        Featured Slots
                                    </h3>
                                    <p class="text-sm text-zinc-400">
                                        {{ adminStats.activeFeatures }} active
                                        features on homepage
                                    </p>
                                </div>
                            </div>
                            <div
                                class="hidden items-center gap-2 text-sm font-medium text-red-400 sm:flex"
                            >
                                <span>Manage</span>
                                <svg
                                    class="size-4 transition-transform group-hover:translate-x-1"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M17 8l4 4m0 0l-4 4m4-4H3"
                                    />
                                </svg>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </AppSidebarLayout>
</template>
