<script setup lang="ts">
import type { Article } from '@/types/models';

import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, ExternalLink, Eye, User } from 'lucide-vue-next';

import PublicLayout from '@/layouts/PublicLayout.vue';

interface Props {
    article: Article;
}

defineProps<Props>();

const formatDate = (dateStr: string | null): string => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};
</script>

<template>
    <PublicLayout>
        <Head :title="article.title" />

        <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- Back link -->
            <Link
                href="/articles"
                class="mb-6 inline-flex items-center gap-1 text-sm text-zinc-400 transition-colors hover:text-white"
            >
                <ArrowLeft class="size-4" />
                Back to articles
            </Link>

            <!-- Article header -->
            <header class="mb-8">
                <h1 class="text-3xl font-bold text-white">
                    {{ article.title }}
                </h1>

                <div
                    class="mt-4 flex flex-wrap items-center gap-4 text-sm text-zinc-400"
                >
                    <span
                        v-if="article.published_at"
                        class="flex items-center gap-1"
                    >
                        <Calendar class="size-4" />
                        {{ formatDate(article.published_at) }}
                    </span>
                    <span v-if="article.author" class="flex items-center gap-1">
                        <User class="size-4" />
                        {{ article.author }}
                    </span>
                    <span
                        v-if="article.view_count"
                        class="flex items-center gap-1"
                    >
                        <Eye class="size-4" />
                        {{ article.view_count }} views
                    </span>
                </div>

                <!-- Tags -->
                <div
                    v-if="article.tags && article.tags.length > 0"
                    class="mt-4 flex flex-wrap gap-2"
                >
                    <span
                        v-for="tag in article.tags"
                        :key="tag.id"
                        class="rounded-full bg-zinc-800 px-3 py-1 text-xs text-zinc-300"
                    >
                        {{ tag.name }}
                    </span>
                </div>

                <!-- Associated movie -->
                <div
                    v-if="
                        article.articleable &&
                        article.articleable_type?.includes('Movie')
                    "
                    class="mt-4 rounded-lg border border-zinc-800 bg-zinc-900/50 p-4"
                >
                    <p class="mb-1 text-xs text-zinc-500">Related movie</p>
                    <Link
                        :href="`/movies/${(article.articleable as any).slug}`"
                        class="text-red-500 transition-colors hover:text-red-400"
                    >
                        {{ (article.articleable as any).title }}
                    </Link>
                </div>
            </header>

            <!-- Featured image -->
            <div
                v-if="article.image_url"
                class="mb-8 overflow-hidden rounded-xl"
            >
                <img
                    :src="article.image_url"
                    :alt="article.title"
                    class="w-full object-cover"
                />
            </div>

            <!-- Article content -->
            <div
                class="prose max-w-none prose-zinc prose-invert prose-headings:text-white prose-p:text-zinc-300 prose-a:text-red-500 prose-a:no-underline hover:prose-a:text-red-400 prose-strong:text-white"
                v-html="article.content"
            />

            <!-- Source link -->
            <div v-if="article.url" class="mt-8 border-t border-zinc-800 pt-6">
                <a
                    :href="article.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-1 text-sm text-zinc-400 transition-colors hover:text-white"
                >
                    <ExternalLink class="size-4" />
                    Read original article
                    <span v-if="article.news_source" class="text-zinc-500">
                        on {{ article.news_source.name }}
                    </span>
                </a>
            </div>
        </div>
    </PublicLayout>
</template>
