<script setup lang="ts">
import type { LucideIcon } from 'lucide-vue-next';

import { Link } from '@inertiajs/vue3';
import {
    BarChart3,
    Compass,
    MessageSquareReply,
    Send,
    TrendingUp,
} from 'lucide-vue-next';

interface XStats {
    published_posts: number;
    scheduled_posts: number;
    failed_posts: number;
    total_impressions: number;
    active_keywords: number;
    engagement_rate: number;
}

interface Props {
    xStats: XStats | null;
}

defineProps<Props>();

interface QuickLink {
    title: string;
    description: string;
    href: string;
    icon: LucideIcon;
    stat?: string | number;
    statLabel?: string;
}

const xApiLinks: QuickLink[] = [
    {
        title: 'Posts',
        description: 'Create & manage',
        href: '/x-posts',
        icon: Send,
    },
    {
        title: 'Analytics',
        description: 'View metrics',
        href: '/dashboard/x-analytics',
        icon: BarChart3,
    },
    {
        title: 'Trends',
        description: 'Monitor keywords',
        href: '/dashboard/x-trends',
        icon: TrendingUp,
    },
    {
        title: 'Auto Replies',
        description: 'Manage rules',
        href: '/dashboard/x-auto-replies',
        icon: MessageSquareReply,
    },
    {
        title: 'Discovery',
        description: 'Curated content',
        href: '/dashboard/x-content-discovery',
        icon: Compass,
    },
];
</script>

<template>
    <div class="rounded-xl bg-zinc-900 p-5 ring-1 ring-zinc-800/70">
        <div class="flex items-center gap-3">
            <div
                class="flex size-9 items-center justify-center rounded-lg bg-sky-500/10"
            >
                <svg
                    class="size-5 text-sky-400"
                    viewBox="0 0 24 24"
                    fill="currentColor"
                >
                    <path
                        d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"
                    />
                </svg>
            </div>
            <div>
                <h3 class="text-base font-semibold text-white">X Platform</h3>
                <p class="text-sm text-zinc-400">Manage your X presence</p>
            </div>
        </div>

        <div
            v-if="xStats"
            class="mt-4 grid grid-cols-2 gap-3 rounded-lg bg-zinc-800/50 p-3 sm:grid-cols-4"
        >
            <div class="text-center">
                <p class="text-lg font-bold text-white">
                    {{ xStats.published_posts }}
                </p>
                <p class="text-xs text-zinc-500">Published</p>
            </div>
            <div class="text-center">
                <p class="text-lg font-bold text-amber-400">
                    {{ xStats.scheduled_posts }}
                </p>
                <p class="text-xs text-zinc-500">Scheduled</p>
            </div>
            <div class="text-center">
                <p class="text-lg font-bold text-white">
                    {{ xStats.total_impressions.toLocaleString() }}
                </p>
                <p class="text-xs text-zinc-500">Impressions</p>
            </div>
            <div class="text-center">
                <p class="text-lg font-bold text-emerald-400">
                    {{ xStats.engagement_rate.toFixed(1) }}%
                </p>
                <p class="text-xs text-zinc-500">Engagement</p>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-5">
            <Link
                v-for="link in xApiLinks"
                :key="link.href"
                :href="link.href"
                class="group flex flex-col items-center gap-2 rounded-lg bg-zinc-800/50 p-3 text-center transition-colors hover:bg-zinc-800"
            >
                <div
                    class="flex size-9 items-center justify-center rounded-lg bg-zinc-700/50 transition-colors group-hover:bg-zinc-700"
                >
                    <component :is="link.icon" class="size-4 text-zinc-300" />
                </div>
                <div>
                    <p class="text-sm font-medium text-white">
                        {{ link.title }}
                    </p>
                    <p class="text-xs text-zinc-500">{{ link.description }}</p>
                </div>
            </Link>
        </div>
    </div>
</template>
